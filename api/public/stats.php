<?php
declare(strict_types=1);
require_once __DIR__ . '/../../inc/bootstrap.php';
require_once __DIR__ . '/../../inc/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=300'); // 5 min browser cache

// Server-side cache (5 min)
$cacheFile = sys_get_temp_dir() . '/trustai_public_stats.json';
$cacheMaxAge = 300;
if (is_file($cacheFile) && (time() - filemtime($cacheFile) < $cacheMaxAge)) {
    echo file_get_contents($cacheFile);
    exit;
}

if (!$pdo instanceof PDO) {
    echo json_encode(['ok' => false, 'error' => 'database_unavailable']);
    exit;
}

try {
    $stats = [
        'businesses' => (int)$pdo->query("SELECT COUNT(*) FROM stores WHERE status = 'active'")->fetchColumn(),
        'ambassadors' => (int)$pdo->query("SELECT COUNT(*) FROM ambassadors WHERE status = 'approved'")->fetchColumn(),
        'total_sales' => (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM orders")->fetchColumn(),
        'total_commission' => (float)$pdo->query("SELECT COALESCE(SUM(commission_amount), 0) FROM orders")->fetchColumn(),
        'total_clicks' => (int)$pdo->query("SELECT COUNT(*) FROM clicks")->fetchColumn(),
        'total_orders' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE ambassador_id IS NOT NULL")->fetchColumn(),
    ];

    $payload = json_encode([
        'ok' => true,
        'stats' => $stats,
        'updated_at' => date('c'),
    ]);

    @file_put_contents($cacheFile, $payload);
    echo $payload;
} catch (Throwable $e) {
    error_log('public/stats.php failed: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'stats_failed']);
}
