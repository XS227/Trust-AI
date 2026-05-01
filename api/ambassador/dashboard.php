<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';

$user = requireRole('ambassador');
$ambassadorId = (int)$user['ambassador_id'];
$storeId = (int)$user['store_id'];
requireAmbassadorAccess($ambassadorId);

$ambStmt = $pdo->prepare('SELECT id, store_id, user_id, name, email, referral_code, status, commission_percent, created_at, approved_at FROM ambassadors WHERE id = :id AND store_id = :store_id LIMIT 1');
$ambStmt->execute(['id' => $ambassadorId, 'store_id' => $storeId]);
$ambassador = $ambStmt->fetch(PDO::FETCH_ASSOC);
if (!$ambassador) {
    jsonResponse(404, ['ok' => false, 'error' => 'ambassador_not_found_for_store']);
}

$ordersStmt = $pdo->prepare('SELECT id, store_id, ambassador_id, referral_code, platform_order_id, customer_name, customer_email, amount, commission_amount, payout_status, created_at FROM orders WHERE ambassador_id = :ambassador_id AND store_id = :store_id ORDER BY created_at DESC LIMIT 200');
$ordersStmt->execute(['ambassador_id' => $ambassadorId, 'store_id' => $storeId]);
$orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

$clicksStmt = $pdo->prepare('SELECT id, source, created_at, referral_code FROM clicks WHERE ambassador_id = :ambassador_id AND store_id = :store_id ORDER BY created_at DESC LIMIT 200');
$clicksStmt->execute(['ambassador_id' => $ambassadorId, 'store_id' => $storeId]);
$clicks = $clicksStmt->fetchAll(PDO::FETCH_ASSOC);

$payoutsStmt = $pdo->prepare('SELECT id, amount, status, invoice_url, created_at, paid_at FROM payouts WHERE ambassador_id = :ambassador_id AND store_id = :store_id ORDER BY created_at DESC LIMIT 200');
$payoutsStmt->execute(['ambassador_id' => $ambassadorId, 'store_id' => $storeId]);
$payouts = $payoutsStmt->fetchAll(PDO::FETCH_ASSOC);

$totalSales = 0.0;
$totalCommission = 0.0;
foreach ($orders as $order) {
    $totalSales += (float)$order['amount'];
    $totalCommission += (float)$order['commission_amount'];
}


$storeStmt = $pdo->prepare('SELECT id, name, domain, platform FROM stores WHERE id = :id LIMIT 1');
$storeStmt->execute(['id' => $storeId]);
$store = $storeStmt->fetch(PDO::FETCH_ASSOC);

$referralLink = '/r/' . $storeId . '/' . urlencode((string)$ambassador['referral_code']);

jsonResponse(200, [
    'ok' => true,
    'ambassador' => $ambassador,
    'store' => $store,
    'metrics' => [
        'clicks' => count($clicks),
        'orders' => count($orders),
        'total_sales' => round($totalSales, 2),
        'total_commission' => round($totalCommission, 2),
    ],
    'referral_link' => $referralLink,
    'orders' => $orders,
    'clicks' => $clicks,
    'payouts' => $payouts,
]);
