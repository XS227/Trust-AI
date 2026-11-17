<?php
declare(strict_types=1);

ob_start();
try {
    require_once __DIR__ . '/../_auth.php';
} catch (Throwable $e) {
    error_log('api/auth/route.php bootstrap failure: ' . $e->getMessage());
}
$bootstrapOutput = trim((string)ob_get_clean());
if ($bootstrapOutput !== '') {
    error_log('api/auth/route.php suppressed bootstrap output: ' . $bootstrapOutput);
}

try {
    if (!function_exists('jsonResponse') || !function_exists('getCurrentUser')) {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        $response = ['ok' => false, 'error' => 'not_logged_in'];
        ob_clean();
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $user = getCurrentUser();
    if (!$user) {
        jsonResponse(200, ['ok' => false, 'error' => 'not_logged_in']);
    }

    $role = (string)($user['role'] ?? '');
    $roleRedirects = [
        'super_admin' => '/super-admin.html',
        'store_admin' => '/store-admin.html',
        'ambassador' => '/ambassador-dashboard.html',
    ];

    if (!isset($roleRedirects[$role])) {
        jsonResponse(200, ['ok' => false, 'error' => 'invalid_role']);
    }

    // B2B referral members may have no Shopify ambassador account.
    if ($role === 'ambassador' && empty($user['ambassador_id']) && isset($pdo) && $pdo instanceof PDO) {
        try {
            $s = $pdo->prepare('SELECT p.id FROM b2b_partners p JOIN b2b_programs g ON g.id=p.program_id WHERE p.user_id=? AND p.status="active" AND g.status="active" LIMIT 1');
            $s->execute([(int)$user['id']]);
            if ($s->fetchColumn()) $roleRedirects['ambassador'] = '/partner-network.html';
        } catch (Throwable $e) {
            // The B2B schema is optional until migrated; preserve existing routes.
        }
    }

    jsonResponse(200, ['ok' => true, 'role' => $role, 'redirect' => $roleRedirects[$role]]);
} catch (Throwable $e) {
    error_log('api/auth/route.php runtime failure: ' . $e->getMessage());
    if (function_exists('jsonResponse')) {
        jsonResponse(200, ['ok' => false, 'error' => 'not_logged_in']);
    }

    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    $response = ['ok' => false, 'error' => 'not_logged_in'];
    ob_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
