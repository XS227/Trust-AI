<?php
session_start();

$cfg = require __DIR__ . '/../../inc/config.local.php';
$vipps = $cfg['vipps'];

require __DIR__ . '/http.php';

// 1) Velg well-known ut fra miljø
$env = $vipps['env'] ?? 'test';
$wellKnown = ($env === 'prod')
  ? 'https://api.vipps.no/access-management-1.0/access/.well-known/openid-configuration'
  : 'https://apitest.vipps.no/access-management-1.0/access/.well-known/openid-configuration';

// 2) Hent metadata (issuer, auth endpoint osv.)
$out = vipps_curl_json($wellKnown);
if (!$out['ok']) {
  http_response_code(500);
  echo "Failed to fetch Vipps metadata\n";
  echo "Well-known: " . htmlspecialchars($wellKnown) . "\n";
  echo "Reason: " . htmlspecialchars($out['error']) . "\n";
  // body kan være HTML/feilside. Hold den kort:
  if (!empty($out['body'])) {
    echo "\nResponse (first 300 chars):\n" . htmlspecialchars(substr($out['body'], 0, 300));
  }
  exit;
}
$wk = $out['json'];

if (empty($wk['authorization_endpoint'])) {
  http_response_code(500);
  echo "Vipps metadata missing authorization_endpoint";
  exit;
}

// 3) CSRF + PKCE
$csrf = bin2hex(random_bytes(16));
$_SESSION['vipps_csrf'] = $csrf;

$verifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
$challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
$_SESSION['pkce_verifier'] = $verifier;

$state = rtrim(strtr(base64_encode(json_encode(['csrf'=>$csrf,'t'=>time()])), '+/', '-_'), '=');

// 4) Redirect til Vipps authorize
$params = [
  'client_id' => $vipps['client_id'],
  'response_type' => 'code',
  'scope' => 'openid profile',
  'redirect_uri' => $vipps['redirect_uri'],   // må matche portalen 1:1
  'state' => $state,
  'code_challenge' => $challenge,
  'code_challenge_method' => 'S256',
];

$authUrl = $wk['authorization_endpoint'] . '?' . http_build_query($params);

header("Location: $authUrl");
exit;
