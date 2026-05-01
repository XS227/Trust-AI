<?php
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$uid = $data["uid"] ?? null;
$email = $data["email"] ?? "";
$displayName = $data["displayName"] ?? "";
$shop = $data["shop"] ?? null;

if (!$uid || !$shop) {
    echo json_encode([
        "ok" => false,
        "error" => "missing uid or shop"
    ]);
    exit;
}

$baseDir = __DIR__;
$ambassadorsDir = $baseDir . "/ambassadors";

if (!is_dir($ambassadorsDir)) {
    mkdir($ambassadorsDir, 0755, true);
}

$cleanUid = preg_replace('/[^a-zA-Z0-9\-_]/', '', $uid);
$cleanShop = preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $shop);

$file = $ambassadorsDir . "/" . $cleanUid . "__" . $cleanShop . ".json";

if (file_exists($file)) {
    $existing = json_decode(file_get_contents($file), true);
    if (is_array($existing) && !empty($existing["code"])) {
        echo json_encode([
            "ok" => true,
            "code" => $existing["code"],
            "existing" => true
        ]);
        exit;
    }
}

$codeBase = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $displayName ?: $email ?: $uid));
$codeBase = substr($codeBase, 0, 8);
if (!$codeBase) {
    $codeBase = "amb";
}

$code = $codeBase . substr(md5($uid . $shop), 0, 6);

$payload = [
    "uid" => $uid,
    "email" => $email,
    "displayName" => $displayName,
    "shop" => $shop,
    "code" => $code,
    "status" => "active",
    "created_at" => date("c"),
    "updated_at" => date("c")
];

file_put_contents(
    $file,
    json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo json_encode([
    "ok" => true,
    "code" => $code
]);
?>
