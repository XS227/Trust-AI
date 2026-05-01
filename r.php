<?php
declare(strict_types=1);
require __DIR__ . '/inc/db.php';

$requestPath = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$storeId = (int)($_GET['store_id'] ?? 0);
$code = trim((string)($_GET['code'] ?? ''));

if (preg_match('#^/r/(\d+)/([A-Za-z0-9_-]{3,120})$#', $requestPath, $m)) {
    $storeId = (int)$m[1];
    $code = $m[2];
}

if ($storeId <= 0 || !preg_match('/^[A-Za-z0-9_-]{3,120}$/', $code)) {
    http_response_code(400);
    echo 'Invalid referral URL';
    exit;
}

$ambStmt = $pdo->prepare('SELECT id FROM ambassadors WHERE store_id = :store_id AND referral_code = :referral_code LIMIT 1');
$ambStmt->execute(['store_id' => $storeId, 'referral_code' => $code]);
$ambassador = $ambStmt->fetch();

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$ipHash = hash('sha256', $ip);

$stmt = $pdo->prepare('INSERT INTO clicks (store_id, ambassador_id, referral_code, source, ip_hash, user_agent, created_at) VALUES (:store_id, :ambassador_id, :referral_code, :source, :ip_hash, :user_agent, NOW())');
$stmt->execute([
    'store_id' => $storeId,
    'ambassador_id' => $ambassador ? (int)$ambassador['id'] : null,
    'referral_code' => $code,
    'source' => 'referral_link',
    'ip_hash' => $ipHash,
    'user_agent' => substr($ua, 0, 255),
]);

$isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
setcookie('trustai_ref', $code, time() + 60 * 60 * 24 * 30, '/', '', $isSecure, true);
setcookie('trustai_store', (string)$storeId, time() + 60 * 60 * 24 * 30, '/', '', $isSecure, true);

header('Location: /');
exit;
