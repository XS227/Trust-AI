<?php
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$shop = $data["shop"] ?? null;
$code = $data["code"] ?? null;
$url = $data["url"] ?? "";

if (!$shop || !$code) {
    echo json_encode([
        "ok" => false,
        "error" => "missing shop or code"
    ]);
    exit;
}

$baseDir = __DIR__;
$clicksDir = $baseDir . "/clicks";

if (!is_dir($clicksDir)) {
    mkdir($clicksDir, 0755, true);
}

$cleanShop = preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $shop);
$cleanCode = preg_replace('/[^a-zA-Z0-9\-_]/', '', $code);

$event = [
    "type" => "referral_click",
    "shop" => $shop,
    "code" => $code,
    "url" => $url,
    "created_at" => date("c"),
    "ip" => $_SERVER["REMOTE_ADDR"] ?? "",
    "user_agent" => $_SERVER["HTTP_USER_AGENT"] ?? ""
];

file_put_contents(
    $clicksDir . "/" . $cleanShop . "__" . $cleanCode . ".log",
    json_encode($event, JSON_UNESCAPED_SLASHES) . PHP_EOL,
    FILE_APPEND
);

echo json_encode([
    "ok" => true
]);
?>
