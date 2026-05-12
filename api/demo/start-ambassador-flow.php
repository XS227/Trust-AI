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
    // Seed demo store so it is findable via /api/public/store-lookup.php
    demoGetOrCreateStore($pdo);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('demo/start-ambassador-flow failed: ' . $e->getMessage());
    header('Location: /login.html?error=demo_setup_failed');
    exit;
}

header('Location: /ambassador-signup.html?demo=1');
exit;
