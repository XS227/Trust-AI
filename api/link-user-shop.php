<?php
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$uid = $data["uid"] ?? null;
$email = $data["email"] ?? "";
$displayName = $data["displayName"] ?? "";
$shop = $data["shop"] ?? null;
$host = $data["host"] ?? "";

if (!$uid || !$shop) {
    echo json_encode([
        "ok" => false,
        "error" => "missing uid or shop"
    ]);
    exit;
}

$baseDir = __DIR__;
$linksDir = $baseDir . "/links";

if (!is_dir($linksDir)) {
    mkdir($linksDir, 0755, true);
}

$cleanUid = preg_replace('/[^a-zA-Z0-9\-_]/', '', $uid);
$cleanShop = preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $shop);

$payload = [
    "uid" => $uid,
    "email" => $email,
    "displayName" => $displayName,
    "shop" => $shop,
    "host" => $host,
    "linked_at" => date("c"),
    "updated_at" => date("c")
];

file_put_contents(
    $linksDir . "/" . $cleanUid . "__" . $cleanShop . ".json",
    json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo json_encode([
    "ok" => true,
    "uid" => $uid,
    "shop" => $shop
]);
?>
