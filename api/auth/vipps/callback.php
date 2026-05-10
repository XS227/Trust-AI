<?php
declare(strict_types=1);

require_once __DIR__ . '/_vipps_common.php';

if (!$pdo instanceof PDO) {
    header('Location: /login.html?error=database_unavailable');
    exit;
}

$cfg = trustaiVippsRequireConfig();

$code = (string)($_GET['code'] ?? '');
$rawState = (string)($_GET['state'] ?? '');
$grantedScope = (string)($_GET['scope'] ?? '');
$vippsErrorCode = (string)($_GET['error'] ?? '');
$vippsErrorDesc = (string)($_GET['error_description'] ?? '');

trustaiVippsDebugLog('callback_entry', [
    'has_code' => $code !== '',
    'has_state' => $rawState !== '',
    'state_len' => strlen($rawState),
    'granted_scope' => $grantedScope,
    'vipps_error' => $vippsErrorCode !== '' ? $vippsErrorCode : null,
    'vipps_error_description' => $vippsErrorDesc !== '' ? $vippsErrorDesc : null,
    'sid_len' => strlen((string)session_id()),
    'cookie_present' => isset($_COOKIE[session_name()]),
    'session_has_csrf' => isset($_SESSION['vipps_csrf']),
    'session_has_pkce' => isset($_SESSION['vipps_pkce_verifier']),
]);

if ($vippsErrorCode !== '') {
    // Forward Vipps' own error details for diagnosis on /login.html.
    $params = ['error' => 'vipps_cancelled', 'vipps_error' => $vippsErrorCode];
    if ($vippsErrorDesc !== '') $params['vipps_error_description'] = $vippsErrorDesc;
    header('Location: /login.html?' . http_build_query($params));
    exit;
}

if ($code === '' || $rawState === '') {
    header('Location: /login.html?error=vipps_missing_code');
    exit;
}

$decoded = json_decode((string)base64_decode(strtr($rawState, '-_', '+/'), true), true);
if (!is_array($decoded) || empty($decoded['csrf'])) {
    trustaiVippsLogStateIssue('bad_state_payload', ['raw_state_len' => strlen($rawState)]);
    header('Location: /login.html?error=vipps_bad_state');
    exit;
}

$sessionCsrf = (string)($_SESSION['vipps_csrf'] ?? '');
if ($sessionCsrf === '' || !hash_equals($sessionCsrf, (string)$decoded['csrf'])) {
    trustaiVippsLogStateIssue('csrf_mismatch', [
        'session_csrf_present' => $sessionCsrf !== '',
        'state_csrf_present' => !empty($decoded['csrf']),
    ]);
    header('Location: /login.html?error=vipps_csrf');
    exit;
}

$verifier = (string)($_SESSION['vipps_pkce_verifier'] ?? '');
if ($verifier === '') {
    trustaiVippsLogStateIssue('missing_pkce_verifier');
    header('Location: /login.html?error=vipps_pkce');
    exit;
}

$intent = (string)($_SESSION['vipps_intent'] ?? ($decoded['intent'] ?? 'login'));
$desiredRole = (string)($_SESSION['vipps_desired_role'] ?? ($decoded['role'] ?? ''));

unset($_SESSION['vipps_csrf'], $_SESSION['vipps_pkce_verifier'], $_SESSION['vipps_intent'], $_SESSION['vipps_desired_role']);

$discovery = trustaiVippsDiscover($cfg);
if (!$discovery || empty($discovery['token_endpoint']) || empty($discovery['userinfo_endpoint'])) {
    header('Location: /login.html?error=vipps_discovery_failed');
    exit;
}

$post = http_build_query([
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $cfg['redirect_uri'],
    'client_id' => $cfg['client_id'],
    'code_verifier' => $verifier,
]);

$ch = curl_init($discovery['token_endpoint']);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $post,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => array_merge(
        ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
        trustaiVippsSystemHeaders($cfg)
    ),
    CURLOPT_USERPWD => $cfg['client_id'] . ':' . $cfg['client_secret'],
]);
$tokenBody = curl_exec($ch);
$tokenHttp = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($tokenHttp < 200 || $tokenHttp >= 300 || !$tokenBody) {
    error_log('Vipps token exchange failed http=' . $tokenHttp . ' body=' . substr((string)$tokenBody, 0, 500));
    header('Location: /login.html?error=vipps_token_failed');
    exit;
}

$token = json_decode((string)$tokenBody, true);
$accessToken = (string)($token['access_token'] ?? '');
if ($accessToken === '') {
    header('Location: /login.html?error=vipps_token_failed');
    exit;
}

$userResult = trustaiVippsHttpGetJson(
    $discovery['userinfo_endpoint'],
    array_merge(
        ['Authorization: Bearer ' . $accessToken],
        trustaiVippsSystemHeaders($cfg)
    )
);

if (!$userResult['ok'] || empty($userResult['json'])) {
    error_log('Vipps userinfo failed http=' . $userResult['http']);
    header('Location: /login.html?error=vipps_userinfo_failed');
    exit;
}

$ui = $userResult['json'];
$vippsSub = (string)($ui['sub'] ?? '');
if ($vippsSub === '') {
    header('Location: /login.html?error=vipps_no_sub');
    exit;
}

$email = strtolower(trim((string)($ui['email'] ?? '')));
$fullName = trim((string)($ui['name'] ?? ''));
if ($fullName === '') {
    $given = trim((string)($ui['given_name'] ?? ''));
    $family = trim((string)($ui['family_name'] ?? ''));
    $fullName = trim($given . ' ' . $family);
}
$phone = trustaiVippsNormalizePhone((string)($ui['phone_number'] ?? ''));
$birthDate = trustaiVippsParseBirthDate((string)($ui['birthdate'] ?? $ui['birth_date'] ?? ''));
$age = trustaiVippsAgeFromBirthDate($birthDate);
$ageVerified = false; // true only when Vipps gave us birthdate AND age >= 18

trustaiVippsDebugLog('callback_userinfo', [
    'has_sub' => !empty($ui['sub']),
    'has_name' => !empty($ui['name']) || !empty($ui['given_name']),
    'has_email' => !empty($ui['email']),
    'has_phone' => !empty($ui['phone_number']),
    'has_birthdate' => $birthDate !== '',
    'age_calculated' => $age,
    'granted_scope' => $grantedScope,
]);

// 18+ gate.
// Strict path: Vipps returned birthdate AND user is under 18 -> hard reject.
// Self-declaration path: Vipps did NOT return birthdate (scope not granted by
// the merchant's Vipps API product subscription) -> proceed but require an
// explicit "I am 18 or older" confirmation on the role picker before any
// role/account work is finalised.
if ($birthDate !== '' && $age !== null && $age < 18) {
    unset($_SESSION['vipps_csrf'], $_SESSION['vipps_pkce_verifier'], $_SESSION['vipps_intent'], $_SESSION['vipps_desired_role']);
    trustaiVippsDebugLog('callback_redirect', ['target' => '/login.html?error=under_18', 'reason' => 'birthdate_verified_under_18']);
    header('Location: /login.html?error=under_18');
    exit;
}
if ($birthDate !== '' && $age !== null && $age >= 18) {
    $ageVerified = true;
}

trustaiVippsEnsureSchema($pdo);

$user = null;
$stmt = $pdo->prepare('SELECT id, email, role, status, store_id, ambassador_id, phone_number, full_name, vipps_sub FROM users WHERE vipps_sub = :sub LIMIT 1');
$stmt->execute(['sub' => $vippsSub]);
$user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

if (!$user && $phone !== '') {
    $stmt = $pdo->prepare('SELECT id, email, role, status, store_id, ambassador_id, phone_number, full_name, vipps_sub FROM users WHERE phone_number = :phone OR phone = :phone LIMIT 1');
    $stmt->execute(['phone' => $phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if (!$user && $email !== '') {
    $stmt = $pdo->prepare('SELECT id, email, role, status, store_id, ambassador_id, phone_number, full_name, vipps_sub FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if ($user) {
    $update = $pdo->prepare(
        'UPDATE users
            SET vipps_sub = :sub,
                provider = COALESCE(provider, "vipps"),
                provider_id = COALESCE(provider_id, :sub2),
                full_name = COALESCE(NULLIF(full_name, ""), :full_name),
                phone_number = COALESCE(NULLIF(phone_number, ""), :phone),
                phone = COALESCE(NULLIF(phone, ""), :phone2),
                birth_date = COALESCE(birth_date, :bdate)
          WHERE id = :id'
    );
    $update->execute([
        'sub' => $vippsSub,
        'sub2' => $vippsSub,
        'full_name' => $fullName !== '' ? $fullName : null,
        'phone' => $phone !== '' ? $phone : null,
        'phone2' => $phone !== '' ? $phone : null,
        'bdate' => $birthDate !== '' ? $birthDate : null,
        'id' => (int)$user['id'],
    ]);
} else {
    if ($email === '') {
        $email = 'vipps-' . substr(hash('sha256', $vippsSub), 0, 16) . '@vipps.trustai.no';
    }
    try {
        $ins = $pdo->prepare(
            'INSERT INTO users
                (email, role, provider, provider_id, vipps_sub, full_name, name, phone, phone_number, birth_date, status, password_hash, created_at, updated_at)
             VALUES
                (:email, "", "vipps", :provider_id, :sub, :full_name, :name, :phone, :phone2, :bdate, "active", "", NOW(), NOW())'
        );
        $ins->execute([
            'email' => $email,
            'provider_id' => $vippsSub,
            'sub' => $vippsSub,
            'full_name' => $fullName !== '' ? $fullName : null,
            'name' => $fullName !== '' ? $fullName : null,
            'phone' => $phone !== '' ? $phone : null,
            'phone2' => $phone !== '' ? $phone : null,
            'bdate' => $birthDate !== '' ? $birthDate : null,
        ]);
    } catch (PDOException $e) {
        error_log('Vipps create user failed: ' . $e->getMessage());
        header('Location: /login.html?error=vipps_create_failed');
        exit;
    }

    $newId = (int)$pdo->lastInsertId();
    $stmt = $pdo->prepare('SELECT id, email, role, status, store_id, ambassador_id, phone_number, full_name, vipps_sub FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $newId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if (!$user) {
    header('Location: /login.html?error=vipps_user_not_found');
    exit;
}

if (!trustaiCanLogin($user)) {
    $msg = urlencode(trustaiBlockedStatusMessage($user));
    header('Location: /login.html?error=' . $msg);
    exit;
}

$currentRole = strtolower((string)($user['role'] ?? ''));

if ($currentRole === '' && in_array($desiredRole, ['ambassador', 'store_admin'], true)) {
    $upd = $pdo->prepare('UPDATE users SET role = :role, updated_at = NOW() WHERE id = :id');
    $upd->execute(['role' => $desiredRole, 'id' => (int)$user['id']]);
    $user['role'] = $desiredRole;
    $currentRole = $desiredRole;
}

trustaiStartSessionForUser($user);

// Carry the age-verification status into the post-login session. The role
// picker / role-setting endpoint enforces it before any role is committed.
$_SESSION['vipps_age_verified'] = $ageVerified;
$_SESSION['vipps_needs_age_confirm'] = !$ageVerified;

// Pick the final redirect target.
$target = null;
$reason = '';
if ($currentRole === '') {
    // New or role-less user → role picker. Anonymous-OAuth temp keys survive
    // trustaiStartSessionForUser()'s session-id rotation.
    $_SESSION['vipps_pending_user_id'] = (int)$user['id'];
    $target = '/vipps-role.html';
    $reason = $ageVerified ? 'new_or_no_role' : 'new_or_no_role_age_unverified';
} elseif (!$ageVerified) {
    // Existing user re-authenticating but we still haven't proven their age.
    // Bounce through the role picker page (which will now just collect age
    // confirmation, since their role is already set).
    $target = '/vipps-role.html?confirm_age=1';
    $reason = 'existing_role_age_unverified';
} elseif ($currentRole === 'ambassador' && empty($user['ambassador_id'])) {
    $target = '/ambassador-signup.html?vipps=1';
    $reason = 'ambassador_missing_application';
} elseif ($currentRole === 'store_admin' && empty($user['store_id'])) {
    $target = '/store-signup.html?vipps=1';
    $reason = 'store_admin_missing_store';
} else {
    $target = trustaiRoleRedirect($user);
    $reason = 'role_dashboard:' . $currentRole;
}

trustaiVippsDebugLog('callback_redirect', [
    'target' => $target,
    'reason' => $reason,
    'role' => $currentRole !== '' ? $currentRole : null,
    'age_verified' => $ageVerified,
    'user_id' => (int)$user['id'],
]);
header('Location: ' . $target);
exit;
