<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';

requireRole('super_admin');
$body = readJsonBody();
$storeId = (int)($body['id'] ?? $body['store_id'] ?? 0);

if ($storeId <= 0) {
    jsonResponse(400, ['ok' => false, 'error' => 'store_id_required']);
}

$stmt = $pdo->prepare('DELETE FROM stores WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $storeId]);

jsonResponse(200, ['ok' => true, 'store_id' => $storeId, 'deleted' => $stmt->rowCount() > 0]);
