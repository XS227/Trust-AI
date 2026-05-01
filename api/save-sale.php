<?php
header("Content-Type: application/json");

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data || !is_array($data)) {
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "error" => "invalid json"
    ]);
    exit;
}

$baseDir = __DIR__;
$salesDir = $baseDir . "/sales";
$ambassadorsDir = $baseDir . "/ambassadors";

if (!is_dir($salesDir)) {
    mkdir($salesDir, 0755, true);
}

if (!is_dir($ambassadorsDir)) {
    mkdir($ambassadorsDir, 0755, true);
}

function clean_key($value) {
    return preg_replace('/[^a-zA-Z0-9._-]/', '', (string)$value);
}

function extract_referral_code(array $data, string $raw): ?string {
    if (!empty($data["attributes"]) && is_array($data["attributes"])) {
        foreach ($data["attributes"] as $name => $value) {
            if ((string)$name === "trustai_ref" && (string)$value !== "") {
                return trim((string)$value);
            }
        }
    }

    if (!empty($data["note_attributes"]) && is_array($data["note_attributes"])) {
        foreach ($data["note_attributes"] as $attr) {
            $name = $attr["name"] ?? "";
            $value = $attr["value"] ?? "";

            if ($name === "trustai_ref" && $value !== "") {
                return trim((string)$value);
            }
        }
    }

    $note = $data["note"] ?? "";
    if ($note && preg_match('/Referral:\s*([a-zA-Z0-9_-]+)/', $note, $m)) {
        return trim($m[1]);
    }

    if (!empty($data["line_items"]) && is_array($data["line_items"])) {
        foreach ($data["line_items"] as $item) {
            if (empty($item["properties"]) || !is_array($item["properties"])) {
                continue;
            }

            foreach ($item["properties"] as $prop) {
                $propName = $prop["name"] ?? "";
                $propValue = $prop["value"] ?? "";
                if (($propName === "_trustai_ref" || $propName === "trustai_ref") && (string)$propValue !== "") {
                    return trim((string)$propValue);
                }
            }
        }
    }

    $landingSite = (string)($data["landing_site"] ?? "");
    if ($landingSite !== "") {
        $query = parse_url($landingSite, PHP_URL_QUERY);
        if (is_string($query)) {
            parse_str($query, $params);
            if (!empty($params["ref"])) {
                return trim((string)$params["ref"]);
            }
        }
    }

    if (strpos($raw, "trustai_ref") !== false) {
        if (preg_match('/trustai_ref[^a-zA-Z0-9_-]*([a-zA-Z0-9_-]+)/', $raw, $m)) {
            return trim($m[1]);
        }
    }

    return null;
}

$shop = $_SERVER["HTTP_X_SHOPIFY_SHOP_DOMAIN"] ?? ($data["shop_domain"] ?? "unknown-shop");
$orderId = $data["id"] ?? null;
$orderName = $data["name"] ?? "";
$email = $data["email"] ?? ($data["contact_email"] ?? "");
$totalPrice = $data["total_price"] ?? "";
$currency = $data["currency"] ?? "";
$createdAt = $data["created_at"] ?? date("c");
$note = $data["note"] ?? "";
$refCode = extract_referral_code($data, $raw);

$cleanShop = clean_key($shop);
$cleanRef = $refCode ? clean_key($refCode) : null;

$lineItems = [];
if (!empty($data["line_items"]) && is_array($data["line_items"])) {
    foreach ($data["line_items"] as $item) {
        $lineItems[] = [
            "product_id" => $item["product_id"] ?? null,
            "variant_id" => $item["variant_id"] ?? null,
            "title" => $item["title"] ?? "",
            "quantity" => $item["quantity"] ?? 0,
            "price" => $item["price"] ?? ""
        ];
    }
}

$salePayload = [
    "shop" => $shop,
    "order_id" => $orderId,
    "order_name" => $orderName,
    "email" => $email,
    "total_price" => $totalPrice,
    "currency" => $currency,
    "created_at" => $createdAt,
    "referral_code" => $refCode,
    "note" => $note,
    "line_items" => $lineItems,
    "saved_at" => date("c")
];

/* lagre én fil per ordre */
if ($orderId) {
    file_put_contents(
        $salesDir . "/" . $cleanShop . "__" . $orderId . ".json",
        json_encode($salePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
}

/* append til samlet logg */
file_put_contents(
    $salesDir . "/sales.log",
    json_encode($salePayload, JSON_UNESCAPED_SLASHES) . PHP_EOL,
    FILE_APPEND
);

/* oppdater ambassador hvis referral finnes */
if ($cleanRef) {
    $ambassadorFile = $ambassadorsDir . "/" . $cleanRef . ".json";

    if (file_exists($ambassadorFile)) {
        $ambassador = json_decode(file_get_contents($ambassadorFile), true);

        if (!is_array($ambassador)) {
            $ambassador = [];
        }

        if (!isset($ambassador["sales"]) || !is_array($ambassador["sales"])) {
            $ambassador["sales"] = [];
        }

        $alreadyExists = false;
        foreach ($ambassador["sales"] as $sale) {
            if (($sale["order_id"] ?? null) == $orderId) {
                $alreadyExists = true;
                break;
            }
        }

        if (!$alreadyExists) {
            $ambassador["sales"][] = [
                "order_id" => $orderId,
                "order_name" => $orderName,
                "shop" => $shop,
                "email" => $email,
                "total_price" => $totalPrice,
                "currency" => $currency,
                "created_at" => $createdAt
            ];
        }

        $ambassador["last_sale_at"] = date("c");
        $ambassador["last_order_id"] = $orderId;
        $ambassador["total_sales_count"] = count($ambassador["sales"]);

        $totalRevenue = 0;
        foreach ($ambassador["sales"] as $sale) {
            $totalRevenue += (float)($sale["total_price"] ?? 0);
        }
        $ambassador["total_revenue"] = number_format($totalRevenue, 2, '.', '');

        file_put_contents(
            $ambassadorFile,
            json_encode($ambassador, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}

echo json_encode([
    "ok" => true,
    "order_id" => $orderId,
    "referral_code" => $refCode
]);
?>
