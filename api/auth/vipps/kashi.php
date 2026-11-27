<?php
declare(strict_types=1);

/*
 * Vipps login broker for kashi.is (a separate server).
 * KASHI sends the browser here with its own one-time "state". After Vipps, callback.php signs the profile
 * with KASHI_BROKER_SECRET (HMAC-SHA256) and posts it server-to-server to KASHI, which returns a
 * one-time completion URL. Set KASHI_BROKER_SECRET to the same value as on the KASHI server.
 */
require_once __DIR__ . '/_vipps_common.php';

$secret = trustaiVippsConfigValue('KASHI_BROKER_SECRET', 'KASHI_BROKER_SECRET');

if (isset($_GET['health'])) {
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    if (strlen($secret) < 32) {
        echo json_encode(['ok' => false]);
        exit;
    }
    // Proves both sides hold the same secret without revealing it.
    echo json_encode(['ok' => true, 'proof' => hash_hmac('sha256', 'kashi-health', $secret)]);
    exit;
}

$kashiState = (string)($_GET['state'] ?? '');
if (strlen($secret) < 32 || !preg_match('/^[A-Za-z0-9_\-]{16,64}$/', $kashiState)) {
    header('Location: https://kashi.is/#login/failed');
    exit;
}

$cfg = trustaiVippsRequireConfig();
$discovery = trustaiVippsDiscover($cfg);
if (!$discovery || empty($discovery['authorization_endpoint'])) {
    header('Location: https://kashi.is/#login/failed');
    exit;
}

$csrf = bin2hex(random_bytes(16));
$verifier = trustaiVippsBase64UrlEncode(random_bytes(32));
$challenge = trustaiVippsBase64UrlEncode(hash('sha256', $verifier, true));

$_SESSION['trustai_epoch'] = TRUSTAI_SESSION_EPOCH;
$_SESSION['vipps_csrf'] = $csrf;
$_SESSION['vipps_pkce_verifier'] = $verifier;
$_SESSION['vipps_intent'] = 'login';
$_SESSION['vipps_desired_role'] = '';
// Kept server-side so the app and KASHI state cannot be swapped through the URL.
$_SESSION['vipps_kashi_state'] = $kashiState;

$state = trustaiVippsBase64UrlEncode(json_encode([
    'csrf' => $csrf,
    'app' => 'kashi',
    't' => time(),
]));

trustaiVippsDebugLog('kashi_login_redirect', ['env' => $cfg['env']]);

header('Location: ' . $discovery['authorization_endpoint'] . '?' . http_build_query([
    'client_id' => $cfg['client_id'],
    'response_type' => 'code',
    'scope' => 'openid name phoneNumber email',
    'redirect_uri' => $cfg['redirect_uri'],
    'state' => $state,
    'code_challenge' => $challenge,
    'code_challenge_method' => 'S256',
]));
exit;
