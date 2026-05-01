<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';

requireRole('super_admin');
$body = readJsonBody();
$storeId = (int)($body['id'] ?? $body['store_id'] ?? 0);
$status = trim((string)($body['status'] ?? 'inactive'));

if ($storeId <= 0) {
    jsonResponse(400, ['ok' => false, 'error' => 'store_id_required']);
}
if (!in_array($status, ['inactive', 'paused', 'active'], true)) {
    jsonResponse(400, ['ok' => false, 'error' => 'invalid_status']);
}

$stmt = $pdo->prepare('UPDATE stores SET status = :status WHERE id = :id');
$stmt->execute(['status' => $status, 'id' => $storeId]);

jsonResponse(200, ['ok' => true, 'store_id' => $storeId, 'status' => $status]);
