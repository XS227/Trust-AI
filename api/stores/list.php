<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';

requireRole('super_admin');

$stmt = $pdo->query('SELECT id, name, domain, public_url, platform, owner_user_id, default_commission_percent, status, contact_name, contact_email, contact_phone, created_at FROM stores ORDER BY created_at DESC');
$stores = $stmt->fetchAll();

jsonResponse(200, ['ok' => true, 'data' => ['stores' => $stores]]);
