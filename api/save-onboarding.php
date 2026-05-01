<?php
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$uid = $data["uid"] ?? null;
$shop = $data["shop"] ?? null;
$fullName = $data["fullName"] ?? "";
$instagram = $data["instagram"] ?? "";
$niche = $data["niche"] ?? "";
$notes = $data["notes"] ?? "";

if (!$uid || !$shop) {
    echo json_encode([
        "ok" => false,
        "error" => "missing uid or shop"
    ]);
    exit;
}

$baseDir = __DIR__;
$onboardingDir = $baseDir . "/onboarding";

if (!is_dir($onboardingDir)) {
    mkdir($onboardingDir, 0755, true);
}

$cleanUid = preg_replace('/[^a-zA-Z0-9\-_]/', '', $uid);
$cleanShop = preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $shop);

$payload = [
    "uid" => $uid,
    "shop" => $shop,
    "fullName" => $fullName,
    "instagram" => $instagram,
    "niche" => $niche,
    "notes" => $notes,
    "saved_at" => date("c")
];

file_put_contents(
    $onboardingDir . "/" . $cleanUid . "__" . $cleanShop . ".json",
    json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo json_encode([
    "ok" => true
]);
?>
