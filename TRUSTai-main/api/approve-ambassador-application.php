<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

$body = readJsonBody();
$ambassadorId = (int)($body['application_id'] ?? $body['ambassador_id'] ?? 0);
if ($ambassadorId <= 0) {
    jsonResponse(400, ['ok' => false, 'error' => 'application_id_required']);
}

$user = requireLogin();
if (!in_array($user['role'], ['super_admin', 'store_admin'], true)) {
    jsonResponse(403, ['ok' => false, 'error' => 'forbidden_role']);
}

$stmt = $pdo->prepare('SELECT id, store_id, referral_code FROM ambassadors WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $ambassadorId]);
$amb = $stmt->fetch();
if (!$amb) {
    jsonResponse(404, ['ok' => false, 'error' => 'ambassador_not_found']);
}
if (!isSuperAdmin($user)) {
    requireStoreAccess((int)$amb['store_id']);
}

$pdo->prepare("UPDATE ambassadors SET status = 'approved', approved_at = NOW() WHERE id = :id")->execute(['id' => $ambassadorId]);
jsonResponse(200, [
    'ok' => true,
    'application_id' => $ambassadorId,
    'status' => 'approved',
    'code' => $amb['referral_code'],
    'dashboard_link' => '/ambassador-dashboard.html',
]);
