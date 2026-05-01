<?php
header("Content-Type: application/json");

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode([
        "ok" => false,
        "error" => "invalid json"
    ]);
    exit;
}

$baseDir = __DIR__;
$salesDir = $baseDir . "/sales";

if (!is_dir($salesDir)) {
    mkdir($salesDir, 0755, true);
}

$shop = $_SERVER["HTTP_X_SHOPIFY_SHOP_DOMAIN"] ?? ($data["shop_domain"] ?? "unknown-shop");
$orderId = $data["id"] ?? null;
$orderName = $data["name"] ?? "";
$email = $data["email"] ?? "";
$totalPrice = $data["total_price"] ?? "";
$currency = $data["currency"] ?? "";
$createdAt = $data["created_at"] ?? date("c");
$note = $data["note"] ?? "";

$cleanShop = preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $shop);
$refCode = null;

/*
  1. prøv note_attributes
*/
if (!empty($data["note_attributes"]) && is_array($data["note_attributes"])) {
    foreach ($data["note_attributes"] as $attr) {
        $name = $attr["name"] ?? "";
        $value = $attr["value"] ?? "";

        if ($name === "trustai_ref" && $value !== "") {
            $refCode = $value;
            break;
        }
    }
}

/*
  2. prøv note-feltet
*/
if (!$refCode && $note) {
    if (preg_match('/Referral:\s*([a-zA-Z0-9\-_]+)/', $note, $m)) {
        $refCode = $m[1];
    }
}

/*
  3. fallback: scan raw payload
*/
if (!$refCode && strpos($raw, "trustai_ref") !== false) {
    if (preg_match('/trustai_ref[^a-zA-Z0-9\-_]*([a-zA-Z0-9\-_]+)/', $raw, $m)) {
        $refCode = $m[1];
    }
}

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

$payload = [
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

if ($orderId) {
    file_put_contents(
        $salesDir . "/" . $cleanShop . "__" . $orderId . ".json",
        json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
}

file_put_contents(
    $salesDir . "/sales.log",
    json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL,
    FILE_APPEND
);

echo json_encode([
    "ok" => true,
    "order_id" => $orderId,
    "referral_code" => $refCode
]);
?>
