<?php
declare(strict_types=1);

require_once __DIR__ . '/_vipps_common.php';
$cfg = trustaiVippsRequireConfig();
$discovery = trustaiVippsDiscover($cfg);
if (!$discovery || empty($discovery['authorization_endpoint'])) {
    header('Location: https://bilvaskexpress.no/booking/?login=discovery_failed');
    exit;
}
$next = (string)($_GET['next'] ?? '/booking/');
if ($next === '' || $next[0] !== '/' || str_starts_with($next, '//')) {
    $next = '/booking/';
}
$csrf = bin2hex(random_bytes(16));
$verifier = trustaiVippsBase64UrlEncode(random_bytes(32));
$challenge = trustaiVippsBase64UrlEncode(hash('sha256', $verifier, true));
$_SESSION['trustai_epoch'] = TRUSTAI_SESSION_EPOCH;
$_SESSION['vipps_csrf'] = $csrf;
$_SESSION['vipps_pkce_verifier'] = $verifier;
$_SESSION['vipps_intent'] = 'login';
$_SESSION['vipps_desired_role'] = '';
$statePayload = [
    'csrf' => $csrf,
    'app' => 'bilvaskexpress',
    'next' => $next,
    't' => time(),
];
$state = trustaiVippsBase64UrlEncode(json_encode($statePayload, JSON_UNESCAPED_UNICODE));
$params = [
    'client_id' => $cfg['client_id'],
    'response_type' => 'code',
    'scope' => 'openid name phoneNumber email',
    'redirect_uri' => $cfg['redirect_uri'],
    'state' => $state,
    'code_challenge' => $challenge,
    'code_challenge_method' => 'S256',
];
trustaiVippsDebugLog('bilvask_login_redirect', [
    'redirect_uri' => $cfg['redirect_uri'],
    'next' => $next,
    'env' => $cfg['env'],
]);
header('Location: ' . $discovery['authorization_endpoint'] . '?' . http_build_query($params));
exit;
