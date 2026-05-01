<?php
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$shop = $data["shop"] ?? null;
$host = $data["host"] ?? "";
$url = $data["url"] ?? "";

if (!$shop) {
    echo json_encode([
        "ok" => false,
        "error" => "missing shop"
    ]);
    exit;
}

$baseDir = __DIR__;
$eventsDir = $baseDir . "/events";

if (!is_dir($eventsDir)) {
    mkdir($eventsDir, 0755, true);
}

$cleanShop = preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $shop);

$event = [
    "type" => "app_open",
    "shop" => $shop,
    "host" => $host,
    "url" => $url,
    "created_at" => date("c"),
    "ip" => $_SERVER["REMOTE_ADDR"] ?? "",
    "user_agent" => $_SERVER["HTTP_USER_AGENT"] ?? ""
];

file_put_contents(
    $eventsDir . "/" . $cleanShop . ".log",
    json_encode($event, JSON_UNESCAPED_SLASHES) . PHP_EOL,
    FILE_APPEND
);

echo json_encode([
    "ok" => true
]);
?>
