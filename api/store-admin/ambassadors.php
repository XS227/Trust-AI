<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';

$user = requireRole('store_admin');
$storeId = (int)$user['store_id'];

$stmt = $pdo->prepare('SELECT id, store_id, user_id, name, email, phone, referral_code, status, commission_percent, created_at, approved_at FROM ambassadors WHERE store_id = :store_id ORDER BY created_at DESC');
$stmt->execute(['store_id' => $storeId]);

jsonResponse(200, ['ok' => true, 'ambassadors' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
