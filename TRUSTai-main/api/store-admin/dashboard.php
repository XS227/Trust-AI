<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';

$user = requireRole('store_admin');
$storeId = (int)$user['store_id'];

$metricsSql = "SELECT
    (SELECT COUNT(*) FROM ambassadors WHERE store_id = :store_id) AS ambassadors,
    (SELECT COUNT(*) FROM ambassadors WHERE store_id = :store_id AND status = 'pending') AS pending_ambassadors,
    (SELECT COALESCE(SUM(amount),0) FROM orders WHERE store_id = :store_id) AS total_sales,
    (SELECT COALESCE(SUM(commission_amount),0) FROM orders WHERE store_id = :store_id) AS total_commission,
    (SELECT COALESCE(SUM(amount),0) FROM payouts WHERE store_id = :store_id AND status IN ('requested','approved')) AS payouts_pending,
    (SELECT COUNT(*) FROM clicks WHERE store_id = :store_id) AS total_clicks";
$metricsStmt = $pdo->prepare($metricsSql);
$metricsStmt->execute(['store_id' => $storeId]);
$metrics = $metricsStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$storeStmt = $pdo->prepare('SELECT id, name, domain, platform, default_commission_percent, status, created_at FROM stores WHERE id = :id LIMIT 1');
$storeStmt->execute(['id' => $storeId]);
$store = $storeStmt->fetch(PDO::FETCH_ASSOC);

jsonResponse(200, ['ok' => true, 'store' => $store, 'metrics' => $metrics]);
