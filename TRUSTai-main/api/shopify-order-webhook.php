<?php
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';

$order = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($order)) {
    jsonResponse(400, ['ok' => false, 'error' => 'invalid_json']);
}

$orderId = (string)($order['id'] ?? '');
$platformOrderId = $orderId !== '' ? $orderId : (string)($order['admin_graphql_api_id'] ?? '');
$shopDomain = strtolower((string)($_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'] ?? $order['shop_domain'] ?? ''));
$customerName = trim((string)(($order['customer']['first_name'] ?? '') . ' ' . ($order['customer']['last_name'] ?? '')));
$customerEmail = (string)($order['email'] ?? $order['contact_email'] ?? '');
$amount = (float)($order['total_price'] ?? 0);

if ($platformOrderId === '' || $shopDomain === '') {
    jsonResponse(400, ['ok' => false, 'error' => 'missing_order_or_shop_domain']);
}

$attrCode = '';
foreach (($order['note_attributes'] ?? []) as $attribute) {
    if (!is_array($attribute)) continue;
    if (($attribute['name'] ?? '') === 'trustai_ref') {
        $attrCode = trim((string)($attribute['value'] ?? ''));
        break;
    }
}
if ($attrCode === '' && !empty($order['attributes']['trustai_ref'])) {
    $attrCode = trim((string)$order['attributes']['trustai_ref']);
}

$storeStmt = $pdo->prepare('SELECT id, default_commission_percent FROM stores WHERE domain = :domain LIMIT 1');
$storeStmt->execute(['domain' => $shopDomain]);
$store = $storeStmt->fetch();
if (!$store) {
    jsonResponse(404, ['ok' => false, 'error' => 'store_not_found_for_domain', 'domain' => $shopDomain]);
}
$storeId = (int)$store['id'];

$ambassadorId = null;
$commissionPercent = 0.0;
$attribution = 'none';
if ($attrCode !== '') {
    // Multi-tenant safety: referral_code matches only inside current store_id.
    $ambStmt = $pdo->prepare("SELECT id, commission_percent FROM ambassadors WHERE store_id = :store_id AND referral_code = :referral_code AND status = 'approved' LIMIT 1");
    $ambStmt->execute(['store_id' => $storeId, 'referral_code' => $attrCode]);
    $ambassador = $ambStmt->fetch();
    if ($ambassador) {
        $ambassadorId = (int)$ambassador['id'];
        $commissionPercent = (float)$ambassador['commission_percent'];
        $attribution = 'matched_ambassador';
    } else {
        $attribution = 'referral_code_not_found_in_store';
    }
}

$commissionAmount = round($amount * ($commissionPercent / 100), 2);

$insert = $pdo->prepare('INSERT INTO orders (store_id, ambassador_id, referral_code, platform_order_id, customer_name, customer_email, amount, commission_amount, payout_status, created_at) VALUES (:store_id, :ambassador_id, :referral_code, :platform_order_id, :customer_name, :customer_email, :amount, :commission_amount, :payout_status, NOW()) ON DUPLICATE KEY UPDATE ambassador_id = VALUES(ambassador_id), referral_code = VALUES(referral_code), customer_name = VALUES(customer_name), customer_email = VALUES(customer_email), amount = VALUES(amount), commission_amount = VALUES(commission_amount)');
$insert->execute([
    'store_id' => $storeId,
    'ambassador_id' => $ambassadorId,
    'referral_code' => $attrCode,
    'platform_order_id' => $platformOrderId,
    'customer_name' => $customerName,
    'customer_email' => $customerEmail,
    'amount' => $amount,
    'commission_amount' => $commissionAmount,
    'payout_status' => 'requested',
]);

jsonResponse(200, [
    'ok' => true,
    'store_id' => $storeId,
    'platform_order_id' => $platformOrderId,
    'referral_code' => $attrCode,
    'ambassador_id' => $ambassadorId,
    'commission_amount' => $commissionAmount,
    'attribution' => $attribution,
]);
