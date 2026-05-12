<?php
declare(strict_types=1);

require_once __DIR__ . '/_demo_helper.php';

requireDemoMode();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

if (!$pdo instanceof PDO) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'database_unavailable']);
    exit;
}

$body        = json_decode(file_get_contents('php://input'), true) ?? [];
$name        = trim((string)($body['name']   ?? DEMO_AMB_NAME));
$email       = trim((string)($body['email']  ?? DEMO_AMB_EMAIL));
$phone       = trim((string)($body['phone']  ?? DEMO_AMB_PHONE));
$storeDomain = trim((string)($body['domain'] ?? DEMO_STORE_DOMAIN));

try {
    $pdo->beginTransaction();
    demoEnsureSchema($pdo);

    // Resolve demo store by domain
    $storeStmt = $pdo->prepare('SELECT id FROM stores WHERE domain = :d AND is_demo = 1 LIMIT 1');
    $storeStmt->execute(['d' => $storeDomain]);
    $storeRow = $storeStmt->fetch(PDO::FETCH_ASSOC);
    if (!$storeRow) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'error' => 'store_not_found', 'message' => 'Fant ikke demo-butikken.']);
        exit;
    }
    $storeId = (int)$storeRow['id'];

    // Create / refresh demo ambassador user
    $user   = demoGetOrCreateUser($pdo, $email, $name, $phone, DEMO_AMB_VIPPS_SUB, 'ambassador');
    $userId = (int)$user['id'];

    // Create ambassador record (auto-approved in demo)
    $ambassadorId = demoGetOrCreateAmbassador($pdo, $userId, $storeId, $email, $name, $phone);

    // Link user ↔ ambassador / store
    $pdo->prepare(
        'UPDATE users SET role="ambassador", store_id=:sid, ambassador_id=:aid, updated_at=NOW() WHERE id=:id'
    )->execute(['sid' => $storeId, 'aid' => $ambassadorId, 'id' => $userId]);

    // Seed demo statistics
    demoSeedData($pdo, $storeId, $ambassadorId);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('demo/ambassador-apply failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'setup_failed', 'message' => $e->getMessage()]);
    exit;
}

// Load full user row
$fullUser = $pdo->prepare(
    'SELECT id, email, role, status, store_id, ambassador_id FROM users WHERE id=:id LIMIT 1'
);
$fullUser->execute(['id' => $userId]);
$userRow = $fullUser->fetch(PDO::FETCH_ASSOC);
if (!$userRow) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'user_not_found']);
    exit;
}

// Start session
demoStartSession($userRow);
$_SESSION['ambassador_id'] = $ambassadorId;
$_SESSION['store_id']      = $storeId;

echo json_encode(['ok' => true, 'redirect' => '/ambassador-dashboard.html?demo=1']);
