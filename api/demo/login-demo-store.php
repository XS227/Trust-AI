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

    // 1. Ensure demo store exists.
    $storeId = demoGetOrCreateStore($pdo);

    // 2. Ensure demo store-admin user exists.
    $user = demoGetOrCreateUser(
        $pdo,
        DEMO_STORE_EMAIL,
        DEMO_STORE_OWNER,
        DEMO_STORE_PHONE,
        DEMO_STORE_VIPPS_SUB,
        'store_admin'
    );
    $userId = (int)$user['id'];

    // 3. Link user ↔ store.
    $pdo->prepare(
        'UPDATE users
            SET role="store_admin", store_id=:sid, updated_at=NOW()
          WHERE id=:id'
    )->execute(['sid' => $storeId, 'id' => $userId]);

    $pdo->prepare(
        'UPDATE stores
            SET owner_user_id=:uid, store_admin_user_id=:uid, updated_at=NOW()
          WHERE id=:id AND (owner_user_id IS NULL OR owner_user_id=:uid)'
    )->execute(['uid' => $userId, 'id' => $storeId]);

    // Reset onboarding status so the wizard always starts fresh in demo mode.
    $pdo->prepare(
        "UPDATE stores
            SET onboarding_status='pending', webhook_status='pending',
                script_status='pending', tracking_status='pending',
                updated_at=NOW()
          WHERE id=:id"
    )->execute(['id' => $storeId]);

    // 4. Also ensure the demo ambassador + data exist so the store dashboard
    //    has meaningful statistics to display.
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

    demoSeedData($pdo, $storeId, $ambassadorId);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('demo/login-demo-store failed: ' . $e->getMessage());
    header('Location: /login.html?error=demo_setup_failed');
    exit;
}

// 5. Load updated user row.
$fullUser = $pdo->prepare(
    'SELECT id, email, role, status, store_id, ambassador_id FROM users WHERE id=:id LIMIT 1'
);
$fullUser->execute(['id' => $userId]);
$userRow = $fullUser->fetch(PDO::FETCH_ASSOC);
if (!$userRow) {
    header('Location: /login.html?error=demo_user_not_found');
    exit;
}

// 6. Start session.
demoStartSession($userRow);
$_SESSION['store_id'] = $storeId;

header('Location: /onboarding.html?demo=1');
exit;
