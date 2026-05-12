<?php
declare(strict_types=1);

require_once __DIR__ . '/../_auth.php';

$user = requireLogin();

if (!$pdo instanceof PDO) {
    jsonResponse(500, ['ok' => false, 'error' => 'database_unavailable']);
}

$storeId = (int)($user['store_id'] ?? 0);
if ($storeId <= 0 && ($user['role'] ?? '') === 'super_admin') {
    $storeId = (int)($_GET['store_id'] ?? 0);
}
if ($storeId <= 0) {
    jsonResponse(422, ['ok' => false, 'error' => 'no_store_associated']);
}

$stmt = $pdo->prepare(
    'SELECT id, name, domain, platform, status, onboarding_status, webhook_status, script_status, tracking_status, is_demo
       FROM stores WHERE id = :id LIMIT 1'
);
$stmt->execute(['id' => $storeId]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$store) {
    jsonResponse(404, ['ok' => false, 'error' => 'store_not_found']);
}

$allVerified = $store['webhook_status'] === 'verified'
    && $store['script_status'] === 'verified'
    && $store['tracking_status'] === 'verified';

jsonResponse(200, [
    'ok'               => true,
    'store_id'         => (int)$store['id'],
    'store_name'       => $store['name'],
    'store_domain'     => $store['domain'] ?? '',
    'platform'         => $store['platform'] ?? '',
    'onboarding_status' => $store['onboarding_status'] ?? 'pending',
    'webhook_status'   => $store['webhook_status'] ?? 'pending',
    'script_status'    => $store['script_status'] ?? 'pending',
    'tracking_status'  => $store['tracking_status'] ?? 'pending',
    'is_demo'          => (bool)($store['is_demo'] ?? false),
    'all_verified'     => $allVerified,
]);
