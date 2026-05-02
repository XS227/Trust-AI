<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';
$user = requireRole('ambassador');
$ambassadorId = (int)$user['ambassador_id'];
$storeId = (int)$user['store_id'];
requireAmbassadorAccess($ambassadorId);

$ambStmt = $pdo->prepare('SELECT id, store_id, user_id, name, email, phone, referral_code, status, commission_percent, created_at, approved_at FROM ambassadors WHERE id = :id AND store_id = :store_id LIMIT 1');
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

$storeStmt = $pdo->prepare('SELECT id, name, domain, platform, public_url, url FROM stores WHERE id = :id LIMIT 1');
$storeStmt->execute(['id' => $storeId]);
$store = $storeStmt->fetch(PDO::FETCH_ASSOC);

// Bygg full URL: prøv public_url først, så url, så https://domain
$baseUrl = '';
if (!empty($store['public_url'])) {
    $baseUrl = rtrim((string)$store['public_url'], '/');
} elseif (!empty($store['url'])) {
    $baseUrl = (string)$store['url'];
    if (!preg_match('#^https?://#i', $baseUrl)) {
        $baseUrl = 'https://' . $baseUrl;
    }
    $baseUrl = rtrim($baseUrl, '/');
} elseif (!empty($store['domain'])) {
    $baseUrl = 'https://' . rtrim((string)$store['domain'], '/');
}

$referralPath = '/r/' . $storeId . '/' . urlencode((string)$ambassador['referral_code']);
$referralLink = $baseUrl . $referralPath;
// Fallback hvis vi ikke fant noen base URL
if ($baseUrl === '') {
    $referralLink = $referralPath;
}

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
    'referral_path' => $referralPath,
    'orders' => $orders,
    'clicks' => $clicks,
    'payouts' => $payouts,
]);
