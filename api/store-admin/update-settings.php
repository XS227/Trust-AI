<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';
$user = requireRole('store_admin');
$storeId = (int)$user['store_id'];
$body = readJsonBody();

$defaultCommission = (float)($body['default_commission_percent'] ?? 0);
if ($defaultCommission <= 0 || $defaultCommission > 100) {
    jsonResponse(400, ['ok' => false, 'error' => 'invalid_default_commission_percent']);
}

$stmt = $pdo->prepare('UPDATE stores SET default_commission_percent = :default_commission_percent WHERE id = :id');
$stmt->execute(['default_commission_percent' => $defaultCommission, 'id' => $storeId]);

jsonResponse(200, ['ok' => true, 'store_id' => $storeId, 'default_commission_percent' => $defaultCommission]);
