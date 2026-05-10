<?php
declare(strict_types=1);

require_once __DIR__ . '/_vipps_common.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    jsonResponse(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

if (!$pdo instanceof PDO) {
    jsonResponse(500, ['ok' => false, 'error' => 'database_unavailable']);
}

$user = requireLogin();

$body = readJsonBody();
$role = strtolower(trim((string)($body['role'] ?? $_POST['role'] ?? '')));
$confirm18 = !empty($body['confirm_18']) || !empty($_POST['confirm_18']);

if ($role !== '' && !in_array($role, ['ambassador', 'store_admin'], true)) {
    jsonResponse(422, ['ok' => false, 'error' => 'invalid_role']);
}

$currentRole = strtolower((string)($user['role'] ?? ''));
if ($role !== '' && $currentRole !== '' && $currentRole !== $role) {
    jsonResponse(409, ['ok' => false, 'error' => 'role_already_set']);
}

// Age verification gate. If Vipps couldn't authoritatively prove the user is
// 18+ (because the merchant's Vipps API product subscription doesn't include
// the birthDate scope), require the user to self-declare here.
$ageVerified = !empty($_SESSION['vipps_age_verified']);
$needsConfirm = !empty($_SESSION['vipps_needs_age_confirm']);

if (!$ageVerified) {
    if (!$confirm18) {
        jsonResponse(422, ['ok' => false, 'error' => 'age_confirmation_required']);
    }
    $_SESSION['vipps_age_verified'] = true;
    $_SESSION['vipps_needs_age_confirm'] = false;
    $needsConfirm = false;
    $ageVerified = true;
    trustaiVippsDebugLog('age_self_declared', ['user_id' => (int)$user['id']]);
}

$effectiveRole = $role !== '' ? $role : $currentRole;

if ($effectiveRole === '') {
    // We have age confirmation but no role yet -> client must POST a role too.
    jsonResponse(422, ['ok' => false, 'error' => 'role_required']);
}

if ($currentRole === '' && $role !== '') {
    $stmt = $pdo->prepare('UPDATE users SET role = :role, updated_at = NOW() WHERE id = :id');
    $stmt->execute(['role' => $role, 'id' => (int)$user['id']]);
    $_SESSION['role'] = $role;
    if (isset($_SESSION['trustai_user']) && is_array($_SESSION['trustai_user'])) {
        $_SESSION['trustai_user']['role'] = $role;
    }
}

unset($_SESSION['vipps_pending_user_id']);

$redirect = $effectiveRole === 'ambassador'
    ? '/ambassador-signup.html?vipps=1'
    : ($effectiveRole === 'store_admin' ? '/store-signup.html?vipps=1' : '/app.html');

trustaiVippsDebugLog('set_role_ok', [
    'role' => $effectiveRole,
    'age_verified' => $ageVerified,
    'redirect' => $redirect,
]);

jsonResponse(200, ['ok' => true, 'role' => $effectiveRole, 'redirect' => $redirect]);
