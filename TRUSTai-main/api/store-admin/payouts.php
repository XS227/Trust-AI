<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';
$user = requireRole('store_admin');
$storeId = (int)$user['store_id'];

$stmt = $pdo->prepare('SELECT id, store_id, ambassador_id, amount, status, invoice_url, created_at, paid_at FROM payouts WHERE store_id = :store_id ORDER BY created_at DESC LIMIT 200');
$stmt->execute(['store_id' => $storeId]);

jsonResponse(200, ['ok' => true, 'payouts' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
