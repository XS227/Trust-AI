<?php
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$shop = $data["shop"] ?? null;
$host = $data["host"] ?? null;

if (!$shop) {
    echo json_encode([
        "ok" => false,
        "error" => "missing shop"
    ]);
    exit;
}

$baseDir = __DIR__;
$shopsDir = $baseDir . "/shops";

if (!is_dir($shopsDir)) {
    mkdir($shopsDir, 0755, true);
}

$cleanShop = preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $shop);

$payload = [
    "shop" => $shop,
    "host" => $host,
    "installed_at" => date("c"),
    "updated_at" => date("c")
];

$file = $shopsDir . "/" . $cleanShop . ".json";

if (file_exists($file)) {
    $existing = json_decode(file_get_contents($file), true);
    if (is_array($existing)) {
        $payload = array_merge($existing, $payload);
    }
}

file_put_contents(
    $file,
    json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

file_put_contents(
    $baseDir . "/shops.txt",
    $shop . PHP_EOL,
    FILE_APPEND
);

echo json_encode([
    "ok" => true,
    "shop" => $shop
]);
?>
