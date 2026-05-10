<?php
declare(strict_types=1);

require_once __DIR__ . '/_vipps_common.php';

$cfg = trustaiVippsRequireConfig();

$intent = strtolower(trim((string)($_GET['intent'] ?? 'auto')));
if (!in_array($intent, ['auto', 'login', 'ambassador', 'store'], true)) {
    $intent = 'auto';
}

$desiredRole = strtolower(trim((string)($_GET['role'] ?? '')));
if (!in_array($desiredRole, ['ambassador', 'store_admin'], true)) {
    $desiredRole = '';
    if ($intent === 'ambassador') {
        $desiredRole = 'ambassador';
    } elseif ($intent === 'store') {
        $desiredRole = 'store_admin';
    }
}

$discovery = trustaiVippsDiscover($cfg);
if (!$discovery || empty($discovery['authorization_endpoint'])) {
    header('Location: /login.html?error=vipps_discovery_failed');
    exit;
}

$csrf = bin2hex(random_bytes(16));
$verifier = trustaiVippsBase64UrlEncode(random_bytes(32));
$challenge = trustaiVippsBase64UrlEncode(hash('sha256', $verifier, true));

// Stamp the current epoch on this anonymous session so the bootstrap in
// _auth.php doesn't destroy it on the callback request and wipe our CSRF.
$_SESSION['trustai_epoch'] = TRUSTAI_SESSION_EPOCH;
$_SESSION['vipps_csrf'] = $csrf;
$_SESSION['vipps_pkce_verifier'] = $verifier;
$_SESSION['vipps_intent'] = $intent;
$_SESSION['vipps_desired_role'] = $desiredRole;

$statePayload = [
    'csrf' => $csrf,
    'intent' => $intent,
    'role' => $desiredRole,
    't' => time(),
];
$state = trustaiVippsBase64UrlEncode(json_encode($statePayload, JSON_UNESCAPED_UNICODE));

$params = [
    'client_id' => $cfg['client_id'],
    'response_type' => 'code',
    'scope' => 'openid name phoneNumber email birthDate',
    'redirect_uri' => $cfg['redirect_uri'],
    'state' => $state,
    'code_challenge' => $challenge,
    'code_challenge_method' => 'S256',
];

trustaiVippsDebugLog('login_redirect', [
    'intent' => $intent,
    'desired_role' => $desiredRole !== '' ? $desiredRole : null,
    'redirect_uri' => $cfg['redirect_uri'],
    'scope' => $params['scope'],
    'sid_len' => strlen((string)session_id()),
    'cookie_present' => isset($_COOKIE[session_name()]),
    'env' => $cfg['env'],
]);

header('Location: ' . $discovery['authorization_endpoint'] . '?' . http_build_query($params));
exit;
