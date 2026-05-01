<?php
header('Content-Type: application/json; charset=utf-8');

$ordersDir = __DIR__ . '/orders';
$orders = [];

if (!is_dir($ordersDir)) {
    echo json_encode([
        'ok' => true,
        'orders' => [],
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

$files = glob($ordersDir . '/*.json') ?: [];
if (count($files) === 0) {
    echo json_encode([
        'ok' => true,
        'orders' => [],
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

foreach ($files as $file) {
    $raw = file_get_contents($file);
    if ($raw === false) {
        continue;
    }

    $order = json_decode($raw, true);
    if (!is_array($order)) {
        continue;
    }

    $orders[] = $order;
}

usort($orders, static function (array $a, array $b): int {
    return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
});

echo json_encode([
    'ok' => true,
    'orders' => $orders,
], JSON_UNESCAPED_SLASHES);
?>
