<?php
declare(strict_types=1);

require_once __DIR__ . '/../_auth.php';
require_once __DIR__ . '/../demo/_demo_helper.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

$user = requireLogin();

if (!$pdo instanceof PDO) {
    jsonResponse(500, ['ok' => false, 'error' => 'database_unavailable']);
}

$body    = readJsonBody();
$step    = strtolower(trim((string)($body['step'] ?? '')));
$simulate = !empty($body['simulate']) || !empty($_SESSION['is_demo']);

$validSteps = ['webhook', 'script', 'tracking'];
if (!in_array($step, $validSteps, true)) {
    jsonResponse(422, ['ok' => false, 'error' => 'invalid_step', 'valid' => $validSteps]);
}

$storeId = (int)($user['store_id'] ?? 0);
if ($storeId <= 0 && ($user['role'] ?? '') === 'super_admin') {
    $storeId = (int)($body['store_id'] ?? 0);
}
if ($storeId <= 0) {
    jsonResponse(422, ['ok' => false, 'error' => 'no_store_associated']);
}

$stmt = $pdo->prepare(
    'SELECT id, is_demo, onboarding_status, webhook_status, script_status, tracking_status
       FROM stores WHERE id = :id LIMIT 1'
);
$stmt->execute(['id' => $storeId]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$store) {
    jsonResponse(404, ['ok' => false, 'error' => 'store_not_found']);
}

$isDemo = (bool)($store['is_demo'] ?? false) || !empty($_SESSION['is_demo']);

$col = $step . '_status';
$pdo->prepare("UPDATE stores SET `$col` = 'verified', updated_at = NOW() WHERE id = :id")
    ->execute(['id' => $storeId]);

// If all three steps are verified, mark onboarding as completed.
$updated = $pdo->prepare(
    'SELECT webhook_status, script_status, tracking_status FROM stores WHERE id = :id LIMIT 1'
);
$updated->execute(['id' => $storeId]);
$fresh = $updated->fetch(PDO::FETCH_ASSOC);

$allDone = ($fresh['webhook_status'] ?? '') === 'verified'
    && ($fresh['script_status'] ?? '') === 'verified'
    && ($fresh['tracking_status'] ?? '') === 'verified';

if ($allDone) {
    $pdo->prepare(
        "UPDATE stores SET onboarding_status = 'completed', updated_at = NOW() WHERE id = :id"
    )->execute(['id' => $storeId]);
}

jsonResponse(200, [
    'ok'              => true,
    'step'            => $step,
    'status'          => 'verified',
    'onboarding_done' => $allDone,
    'redirect'        => $allDone ? ($isDemo ? '/store-admin.html?demo=1' : '/store-admin.html') : null,
]);
