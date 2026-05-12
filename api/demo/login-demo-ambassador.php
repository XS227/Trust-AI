<?php
declare(strict_types=1);

require_once __DIR__ . '/_demo_helper.php';

requireDemoMode();

if (!$pdo instanceof PDO) {
    header('Location: /login.html?error=database_unavailable');
    exit;
}

try {
    $pdo->beginTransaction();

    demoEnsureSchema($pdo);

    // 1. Ensure demo store exists and is fully onboarded.
    $storeId = demoGetOrCreateStore($pdo);

    // 2. Ensure demo ambassador user exists.
    $user = demoGetOrCreateUser(
        $pdo,
        DEMO_AMB_EMAIL,
        DEMO_AMB_NAME,
        DEMO_AMB_PHONE,
        DEMO_AMB_VIPPS_SUB,
        'ambassador'
    );
    $userId = (int)$user['id'];

    // 3. Ensure ambassador record exists (auto-approved in demo mode).
    $ambassadorId = demoGetOrCreateAmbassador(
        $pdo,
        $userId,
        $storeId,
        DEMO_AMB_EMAIL,
        DEMO_AMB_NAME,
        DEMO_AMB_PHONE
    );

    // 4. Link user ↔ ambassador/store.
    $pdo->prepare(
        'UPDATE users
            SET role="ambassador", store_id=:sid, ambassador_id=:aid,
                updated_at=NOW()
          WHERE id=:id'
    )->execute(['sid' => $storeId, 'aid' => $ambassadorId, 'id' => $userId]);

    // 5. Seed demo data (idempotent).
    demoSeedData($pdo, $storeId, $ambassadorId);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('demo/login-demo-ambassador failed: ' . $e->getMessage());
    header('Location: /login.html?error=demo_setup_failed');
    exit;
}

// 6. Load the full user row (ambassador_id and store_id are now set).
$fullUser = $pdo->prepare(
    'SELECT id, email, role, status, store_id, ambassador_id FROM users WHERE id=:id LIMIT 1'
);
$fullUser->execute(['id' => $userId]);
$userRow = $fullUser->fetch(PDO::FETCH_ASSOC);
if (!$userRow) {
    header('Location: /login.html?error=demo_user_not_found');
    exit;
}

// 7. Start session.
demoStartSession($userRow);
$_SESSION['ambassador_id'] = $ambassadorId;
$_SESSION['store_id']      = $storeId;

header('Location: /ambassador-dashboard.html?demo=1');
exit;
