<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/_auth_common.php';

header('Content-Type: application/json; charset=utf-8');

$domain = strtolower(trim((string)($_GET['domain'] ?? '')));
$domain = preg_replace('#^https?://#i', '', $domain);
$domain = preg_replace('#/.*$#', '', $domain);
$domain = rtrim($domain, '/');

if ($domain === '' || strlen($domain) > 253) {
    echo json_encode(['ok' => false, 'error' => 'invalid_domain']);
    exit;
}

if (!$pdo instanceof PDO) {
    echo json_encode(['ok' => false, 'error' => 'database_unavailable']);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT name, domain, platform, status, default_commission_percent
       FROM stores
      WHERE domain = :domain AND status NOT IN ("deleted","blocked")
      LIMIT 1'
);
$stmt->execute(['domain' => $domain]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    echo json_encode(['ok' => false, 'error' => 'not_found']);
    exit;
}

echo json_encode([
    'ok' => true,
    'name' => $store['name'],
    'domain' => $store['domain'],
    'platform' => $store['platform'] ?? 'Nettbutikk',
    'status' => $store['status'],
    'commission_percent' => (float)($store['default_commission_percent'] ?? 10),
]);
