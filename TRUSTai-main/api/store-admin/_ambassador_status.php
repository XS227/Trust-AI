<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';

$body = readJsonBody();
$ambassadorId = (int)($body['ambassador_id'] ?? 0);
$status = trim((string)($body['status'] ?? ''));

if ($ambassadorId <= 0 || $status === '') {
    jsonResponse(400, ['ok' => false, 'error' => 'ambassador_id_and_status_required']);
}

$user = requireLogin();
if (!in_array($user['role'], ['super_admin', 'store_admin'], true)) {
    jsonResponse(403, ['ok' => false, 'error' => 'forbidden_role']);
}

$stmt = $pdo->prepare('SELECT id, store_id FROM ambassadors WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $ambassadorId]);
$ambassador = $stmt->fetch();
if (!$ambassador) {
    jsonResponse(404, ['ok' => false, 'error' => 'ambassador_not_found']);
}

if (!isSuperAdmin($user)) {
    requireStoreAccess((int)$ambassador['store_id']);
}

$update = $pdo->prepare('UPDATE ambassadors SET status = :status, approved_at = CASE WHEN :status = "approved" THEN NOW() ELSE approved_at END WHERE id = :id');
$update->execute(['status' => $status, 'id' => $ambassadorId]);

jsonResponse(200, ['ok' => true, 'ambassador_id' => $ambassadorId, 'status' => $status]);
