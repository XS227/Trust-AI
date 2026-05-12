<?php
declare(strict_types=1);

require_once __DIR__ . '/_demo_helper.php';

requireDemoMode();

if (!$pdo instanceof PDO) {
    jsonResponse(500, ['ok' => false, 'error' => 'database_unavailable']);
}

// Optionally require super_admin when called directly (not from demo login flow).
$calledDirectly = ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET'
    || !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
if ($calledDirectly) {
    $user = getCurrentUser();
    if ($user && ($user['role'] ?? '') !== 'super_admin') {
        jsonResponse(403, ['ok' => false, 'error' => 'forbidden_role']);
    }
}

try {
    $pdo->beginTransaction();

    demoEnsureSchema($pdo);
    $storeId = demoGetOrCreateStore($pdo);

    $ambUser = demoGetOrCreateUser(
        $pdo,
        DEMO_AMB_EMAIL,
        DEMO_AMB_NAME,
        DEMO_AMB_PHONE,
        DEMO_AMB_VIPPS_SUB,
        'ambassador'
    );
    $ambUserId = (int)$ambUser['id'];

    $ambassadorId = demoGetOrCreateAmbassador(
        $pdo,
        $ambUserId,
        $storeId,
        DEMO_AMB_EMAIL,
        DEMO_AMB_NAME,
        DEMO_AMB_PHONE
    );
    $pdo->prepare(
        'UPDATE users SET store_id=:sid, ambassador_id=:aid, updated_at=NOW() WHERE id=:id'
    )->execute(['sid' => $storeId, 'aid' => $ambassadorId, 'id' => $ambUserId]);

    $storeUser = demoGetOrCreateUser(
        $pdo,
        DEMO_STORE_EMAIL,
        DEMO_STORE_OWNER,
        DEMO_STORE_PHONE,
        DEMO_STORE_VIPPS_SUB,
        'store_admin'
    );
    $storeUserId = (int)$storeUser['id'];
    $pdo->prepare(
        'UPDATE users SET role="store_admin", store_id=:sid, updated_at=NOW() WHERE id=:id'
    )->execute(['sid' => $storeId, 'id' => $storeUserId]);
    $pdo->prepare(
        'UPDATE stores SET owner_user_id=:uid, store_admin_user_id=:uid, updated_at=NOW()
          WHERE id=:id AND (owner_user_id IS NULL OR owner_user_id=:uid)'
    )->execute(['uid' => $storeUserId, 'id' => $storeId]);

    demoSeedData($pdo, $storeId, $ambassadorId);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(500, ['ok' => false, 'error' => 'seed_failed', 'message' => $e->getMessage()]);
}

jsonResponse(200, [
    'ok'           => true,
    'store_id'     => $storeId,
    'ambassador_id' => $ambassadorId,
    'message'      => 'Demo data seeded successfully.',
]);
