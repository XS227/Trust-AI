<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (defined('APP_DEBUG') && APP_DEBUG) {
    echo json_encode([
        'session_id' => session_id(),
        'session_data' => array_keys($_SESSION),
    ]);
    exit;
}

$baseDir = __DIR__;
$ambassadorsDir = $baseDir . '/ambassadors';
$salesDir = $baseDir . '/sales';

function normalize_code(string $value): string
{
    return preg_replace('/[^a-zA-Z0-9._-]/', '', trim($value));
}

function parse_amount($value): float
{
    if (is_int($value) || is_float($value)) {
        return (float)$value;
    }

    if (!is_string($value)) {
        return 0.0;
    }

    $normalized = str_replace(',', '.', trim($value));
    return is_numeric($normalized) ? (float)$normalized : 0.0;
}

function load_sales_from_logs(string $salesDir, string $code): array
{
    if (!is_dir($salesDir)) {
        return [];
    }

    $orders = [];
    $paths = glob($salesDir . '/*.json') ?: [];

    foreach ($paths as $path) {
        $raw = file_get_contents($path);
        if ($raw === false) {
            continue;
        }

        $sale = json_decode($raw, true);
        if (!is_array($sale)) {
            continue;
        }

        $saleCode = normalize_code((string)($sale['referral_code'] ?? ''));
        if ($saleCode === '' || strcasecmp($saleCode, $code) !== 0) {
            continue;
        }

        $orders[] = [
            'order_id' => $sale['order_id'] ?? null,
            'order_name' => $sale['order_name'] ?? '',
            'email' => $sale['email'] ?? '',
            'shop' => $sale['shop'] ?? '',
            'total_price' => parse_amount($sale['total_price'] ?? 0),
            'currency' => $sale['currency'] ?? '',
            'created_at' => $sale['created_at'] ?? null,
        ];
    }

    usort($orders, static function (array $a, array $b): int {
        return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
    });

    return $orders;
}

$codeFilter = normalize_code((string)($_GET['code'] ?? ''));
$emailFilter = strtolower(trim($_GET['email'] ?? ''));
$commissionRate = 0.2; // 20%
$ambassadors = [];

if (is_dir($ambassadorsDir)) {
    $paths = glob($ambassadorsDir . '/*.json') ?: [];

    foreach ($paths as $path) {
        $raw = file_get_contents($path);
        if ($raw === false) {
            continue;
        }

        $ambassador = json_decode($raw, true);
        if (!is_array($ambassador)) {
            continue;
        }

        $filenameCode = pathinfo($path, PATHINFO_FILENAME);
        $code = normalize_code((string)($ambassador['code'] ?? $filenameCode));

        if ($code === '') {
            continue;
        }

        if ($codeFilter !== '' && strcasecmp($code, $codeFilter) !== 0) {
            continue;
        }
        if ($emailFilter !== '' && strtolower($ambassador['email'] ?? '') !== $emailFilter) {
            continue;
        }

        $orders = [];
        if (!empty($ambassador['sales']) && is_array($ambassador['sales'])) {
            foreach ($ambassador['sales'] as $sale) {
                if (!is_array($sale)) {
                    continue;
                }

                $orders[] = [
                    'order_id' => $sale['order_id'] ?? null,
                    'order_name' => $sale['order_name'] ?? '',
                    'email' => $sale['email'] ?? '',
                    'shop' => $sale['shop'] ?? ($ambassador['shop'] ?? ''),
                    'total_price' => parse_amount($sale['total_price'] ?? 0),
                    'currency' => $sale['currency'] ?? '',
                    'created_at' => $sale['created_at'] ?? null,
                ];
            }
        } else {
            $orders = load_sales_from_logs($salesDir, $code);
        }

        $totalRevenue = 0.0;
        foreach ($orders as $order) {
            $totalRevenue += parse_amount($order['total_price'] ?? 0);
        }
        $commission = $totalRevenue * $commissionRate;

        usort($orders, static function (array $a, array $b): int {
            return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
        });

        $name = (string)($ambassador['displayName'] ?? $ambassador['name'] ?? $ambassador['email'] ?? $ambassador['uid'] ?? 'Unknown');
        $shop = (string)($ambassador['shop'] ?? '');
        if ($shop === '' && !empty($orders[0]['shop'])) {
            $shop = (string)$orders[0]['shop'];
        }

        $ambassadors[] = [
            'ambassador_name' => $name,
            'code' => $code,
            'referral_code' => $code,
            'shop' => $shop,
            'total_revenue' => round($totalRevenue, 2),
            'commission' => round($commission, 2),
            'sales_count' => count($orders),
            'orders' => $orders,
        ];
    }
}

usort($ambassadors, static function (array $a, array $b): int {
    return $b['sales_count'] <=> $a['sales_count'];
});

echo json_encode([
    'ok' => true,
    'count' => count($ambassadors),
    'ambassadors' => $ambassadors,
], JSON_UNESCAPED_SLASHES);
