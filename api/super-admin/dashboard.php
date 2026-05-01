<?php
declare(strict_types=1);

ob_start();
try {
    require_once __DIR__ . '/../_auth.php';
} catch (Throwable $e) {
    error_log('api/super-admin/dashboard.php bootstrap failure: ' . $e->getMessage());
}
$bootstrapOutput = trim((string)ob_get_clean());
if ($bootstrapOutput !== '') {
    error_log('api/super-admin/dashboard.php suppressed bootstrap output: ' . $bootstrapOutput);
}

try {
    if (!function_exists('jsonResponse') || !function_exists('getCurrentUser')) {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'auth_problem'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    global $pdo;
    if (!$pdo instanceof PDO) {
        error_log('api/super-admin/dashboard.php missing DB connection');
        jsonResponse(200, ['ok' => false, 'error' => 'auth_problem']);
    }

    $currentUser = getCurrentUser();
    error_log('super-admin/dashboard.php getCurrentUser(): ' . json_encode($currentUser, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    if (empty($currentUser)) {
        jsonResponse(200, ['ok' => false, 'error' => 'auth_problem']);
    }
    if (($currentUser['role'] ?? '') !== 'super_admin') {
        jsonResponse(200, ['ok' => false, 'error' => 'role_mapping_error', 'role' => $currentUser['role'] ?? null]);
    }

    $storeId = (int)($_GET['store_id'] ?? 0);
    $where = $storeId > 0 ? ' WHERE store_id = :store_id ' : '';
    $ambWhere = $storeId > 0 ? ' WHERE a.store_id = :store_id ' : '';
    $params = $storeId > 0 ? ['store_id' => $storeId] : [];

    if ($storeId > 0) {
        $metricsStmt = $pdo->prepare("SELECT
            (SELECT COUNT(*) FROM stores WHERE id = :store_id) AS total_stores,
            (SELECT COUNT(*) FROM ambassadors WHERE store_id = :store_id) AS total_ambassadors,
            (SELECT COUNT(*) FROM ambassadors WHERE store_id = :store_id AND status = 'pending') AS pending_ambassadors,
            (SELECT COALESCE(SUM(amount),0) FROM orders WHERE store_id = :store_id) AS total_sales,
            (SELECT COALESCE(SUM(commission_amount),0) FROM orders WHERE store_id = :store_id) AS total_commission,
            (SELECT COALESCE(SUM(amount),0) FROM payouts WHERE store_id = :store_id AND status IN ('requested','approved')) AS payouts_pending");
        $metricsStmt->execute(['store_id' => $storeId]);
        $metrics = $metricsStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } else {
        $metrics = [
            'total_stores' => (int)$pdo->query('SELECT COUNT(*) FROM stores')->fetchColumn(),
            'total_ambassadors' => (int)$pdo->query('SELECT COUNT(*) FROM ambassadors')->fetchColumn(),
            'pending_ambassadors' => (int)$pdo->query("SELECT COUNT(*) FROM ambassadors WHERE status = 'pending'")->fetchColumn(),
            'total_sales' => (float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM orders')->fetchColumn(),
            'total_commission' => (float)$pdo->query('SELECT COALESCE(SUM(commission_amount),0) FROM orders')->fetchColumn(),
            'payouts_pending' => (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payouts WHERE status IN ('requested','approved')")->fetchColumn(),
        ];
    }

    $stores = $pdo->query('SELECT id, name, domain, public_url, platform, owner_user_id, default_commission_percent, status, contact_name, contact_email, contact_phone, created_at FROM stores ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

    $ambStmt = $pdo->prepare('SELECT a.id, a.store_id, a.user_id, a.name, a.email, a.referral_code, a.status, a.commission_percent, a.created_at, a.approved_at, s.name AS store_name FROM ambassadors a LEFT JOIN stores s ON s.id=a.store_id' . $ambWhere . ' ORDER BY a.created_at DESC LIMIT 300');
    $ambStmt->execute($params);

    $orderStmt = $pdo->prepare('SELECT id, store_id, ambassador_id, referral_code, platform_order_id, customer_email, amount, commission_amount, payout_status, created_at FROM orders' . $where . ' ORDER BY created_at DESC LIMIT 300');
    $orderStmt->execute($params);

    $payoutStmt = $pdo->prepare('SELECT id, store_id, ambassador_id, amount, status, invoice_url, created_at, paid_at FROM payouts' . $where . ' ORDER BY created_at DESC LIMIT 300');
    $payoutStmt->execute($params);

    jsonResponse(200, [
        'ok' => true,
        'data' => [
            'metrics' => $metrics,
            'stores' => $stores,
            'ambassadors' => $ambStmt->fetchAll(PDO::FETCH_ASSOC),
            'orders' => $orderStmt->fetchAll(PDO::FETCH_ASSOC),
            'payouts' => $payoutStmt->fetchAll(PDO::FETCH_ASSOC),
        ],
    ]);
} catch (Throwable $e) {
    error_log('api/super-admin/dashboard.php runtime failure: ' . $e->getMessage());
    if (function_exists('jsonResponse')) {
        jsonResponse(200, ['ok' => false, 'error' => 'auth_problem']);
    }

    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'auth_problem'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
