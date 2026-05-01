<?php
session_start();

$configPath = __DIR__ . '/../../inc/config.local.php';
if (!file_exists($configPath)) {
  http_response_code(500);
  echo 'Missing config.local.php. Run installer.';
  exit;
}

$config = require __DIR__ . '/../../inc/config.local.php';
$vipps = $config['vipps'] ?? null;

if (!$vipps || !is_array($vipps)) {
  http_response_code(500);
  echo 'Missing Vipps config';
  exit;
}

$subscriptionKey = $vipps['subscription_key_primary'] ?? ($vipps['subscription_key'] ?? '');
$merchantSerial = $vipps['merchantSerialNumber'] ?? ($vipps['merchant_serial_number'] ?? '');

if ($subscriptionKey === '' || $merchantSerial === '') {
  http_response_code(500);
  echo 'Missing Vipps headers config';
  exit;
}

$env = $vipps['env'] ?? 'test';
$wellKnown = $vipps['well_known'] ?? ($env === 'prod'
  ? 'https://api.vipps.no/access-management-1.0/access/.well-known/openid-configuration'
  : 'https://apitest.vipps.no/access-management-1.0/access/.well-known/openid-configuration'
);

function vippsSystemHeaders(array $vipps, string $subscriptionKey, string $merchantSerial): array
{
  return [
    'Ocp-Apim-Subscription-Key: ' . $subscriptionKey,
    'Merchant-Serial-Number: ' . $merchantSerial,
    'Vipps-System-Name: TrustAI',
    'Vipps-System-Version: 1.0.0',
    'Vipps-System-Plugin-Name: TrustAI-Referral',
    'Vipps-System-Plugin-Version: 1.0.0',
  ];
}

// 1) Basic params
$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;
if (!$code || !$state) {
  http_response_code(400);
  echo 'Missing code/state';
  exit;
}

// 2) Verify state/csrf
$decoded = json_decode(base64_decode(strtr($state, '-_', '+/')), true);
if (!$decoded || empty($decoded['csrf']) || empty($_SESSION['vipps_csrf'])) {
  http_response_code(400);
  echo 'Invalid state';
  exit;
}
if (!hash_equals($_SESSION['vipps_csrf'], $decoded['csrf'])) {
  http_response_code(400);
  echo 'CSRF mismatch';
  exit;
}

// 3) Fetch well-known to get token endpoint
if (empty($vipps['token_endpoint']) || empty($vipps['userinfo_endpoint'])) {
  $ch = curl_init($wellKnown);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => array_merge(
      ['Accept: application/json'],
      vippsSystemHeaders($vipps, $subscriptionKey, $merchantSerial)
    ),
  ]);
  $wkJson = curl_exec($ch);
  $wkHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  $wk = ($wkHttp >= 200 && $wkHttp < 300 && $wkJson) ? json_decode($wkJson, true) : null;
  if (!$wk || empty($wk['token_endpoint'])) {
    http_response_code(500);
    echo 'Missing token endpoint';
    exit;
  }

  $vipps['token_endpoint'] = $vipps['token_endpoint'] ?: ($wk['token_endpoint'] ?? '');
  $vipps['userinfo_endpoint'] = $vipps['userinfo_endpoint'] ?: ($wk['userinfo_endpoint'] ?? '');
}

// 4) Token exchange
$verifier = $_SESSION['pkce_verifier'] ?? null;
if (!$verifier) {
  http_response_code(400);
  echo 'Missing PKCE verifier';
  exit;
}

$post = http_build_query([
  'grant_type' => 'authorization_code',
  'code' => $code,
  'redirect_uri' => $vipps['redirect_uri'] ?? '',
  'client_id' => $vipps['client_id'] ?? '',
  'code_verifier' => $verifier,
]);

$ch = curl_init($vipps['token_endpoint']);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => $post,
  CURLOPT_HTTPHEADER => array_merge(
    ['Content-Type: application/x-www-form-urlencoded'],
    vippsSystemHeaders($vipps, $subscriptionKey, $merchantSerial)
  ),
  // client_secret brukes normalt i token-kall for confidential clients:
  CURLOPT_USERPWD => ($vipps['client_id'] ?? '') . ':' . ($vipps['client_secret'] ?? ''),
]);
$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http < 200 || $http >= 300) {
  http_response_code(500);
  echo "Token exchange failed. HTTP $http\n$resp";
  exit;
}

$token = json_decode($resp, true);
if (!$token || empty($token['access_token'])) {
  http_response_code(500);
  echo 'Invalid token response';
  exit;
}

// 5) (Valgfritt) userinfo
$userinfo = null;
if (!empty($vipps['userinfo_endpoint'])) {
  $ch = curl_init($vipps['userinfo_endpoint']);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => array_merge(
      [
        'Authorization: Bearer ' . $token['access_token'],
        'Accept: application/json',
      ],
      vippsSystemHeaders($vipps, $subscriptionKey, $merchantSerial)
    ),
  ]);
  $uResp = curl_exec($ch);
  $uHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($uHttp >= 200 && $uHttp < 300) {
    $userinfo = json_decode($uResp, true);
  }
}

// 6) Lag innlogging i session
// Vipps/OIDC gir vanligvis en stabil "sub" per bruker i samme sales unit.
$_SESSION['logged_in'] = true;
$_SESSION['vipps_user'] = [
  'sub' => $userinfo['sub'] ?? null,
  'name' => $userinfo['name'] ?? ($userinfo['given_name'] ?? 'User'),
  'raw' => $userinfo,
];

// 7) Bind referral (hvis bruker kom via /r/<code>)
if (!empty($decoded['ref'])) {
  setcookie('trustai_ref', $decoded['ref'], time() + 60 * 60 * 24 * 30, '/', '', true, true);
}

// 8) Redirect tilbake til forsiden/dashboard
$redirectTarget = $vipps['post_login_redirect'] ?? '/?logged=1';
if (!empty($decoded['redirect']) && str_starts_with($decoded['redirect'], '/') && !str_starts_with($decoded['redirect'], '//')) {
  $redirectTarget = $decoded['redirect'];
}

header('Location: ' . $redirectTarget);
exit;
