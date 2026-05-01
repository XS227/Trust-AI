<?php
declare(strict_types=1);

require_once __DIR__ . '/_auth_common.php';

if (!$pdo instanceof PDO) {
    header('Location: /login.html?error=database_unavailable');
    exit;
}

$action = (string)($_GET['action'] ?? 'start');
$clientId = trustaiGetEnv('GOOGLE_CLIENT_ID');
$clientSecret = trustaiGetEnv('GOOGLE_CLIENT_SECRET');
$redirectUri = trustaiGetEnv('GOOGLE_REDIRECT_URI', 'https://trustai.no/api/auth/google.php?action=callback');

if (!$clientId || !$clientSecret || !$redirectUri) {
    header('Location: /login.html?error=google_not_configured');
    exit;
}

if ($action === 'start') {
    $state = bin2hex(random_bytes(24));
    $_SESSION['google_oauth_state'] = $state;

    $query = http_build_query([
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'state' => $state,
        'access_type' => 'online',
        'prompt' => 'select_account',
    ]);

    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    exit;
}

if ($action !== 'callback') {
    header('Location: /login.html?error=invalid_google_action');
    exit;
}

if (!empty($_GET['error'])) {
    header('Location: /login.html?error=google_cancelled');
    exit;
}

$state = (string)($_GET['state'] ?? '');
if ($state === '' || !hash_equals((string)($_SESSION['google_oauth_state'] ?? ''), $state)) {
    unset($_SESSION['google_oauth_state']);
    header('Location: /login.html?error=invalid_google_state');
    exit;
}
unset($_SESSION['google_oauth_state']);

$code = (string)($_GET['code'] ?? '');
if ($code === '') {
    header('Location: /login.html?error=missing_google_code');
    exit;
}

$tokenCtx = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]),
        'ignore_errors' => true,
        'timeout' => 15,
    ],
]);
$tokenResp = file_get_contents('https://oauth2.googleapis.com/token', false, $tokenCtx);
$tokenData = json_decode((string)$tokenResp, true);
$accessToken = (string)($tokenData['access_token'] ?? '');
if ($accessToken === '') {
    header('Location: /login.html?error=google_token_failed');
    exit;
}

$userCtx = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Authorization: Bearer {$accessToken}\r\n",
        'ignore_errors' => true,
        'timeout' => 15,
    ],
]);
$userResp = file_get_contents('https://openidconnect.googleapis.com/v1/userinfo', false, $userCtx);
$google = json_decode((string)$userResp, true);
$email = strtolower(trim((string)($google['email'] ?? '')));
$emailVerified = (bool)($google['email_verified'] ?? false);
$googleId = trim((string)($google['sub'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !$emailVerified || $googleId === '') {
    header('Location: /login.html?error=google_unverified_email');
    exit;
}

$pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS provider VARCHAR(40) NULL, ADD COLUMN IF NOT EXISTS provider_id VARCHAR(190) NULL, ADD COLUMN IF NOT EXISTS status VARCHAR(40) NOT NULL DEFAULT 'active'");
$stmt = $pdo->prepare('SELECT id,email,role,store_id,ambassador_id,status FROM users WHERE email=:email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $ins = $pdo->prepare("INSERT INTO users (email, role, provider, provider_id, status, created_at) VALUES (:email, 'ambassador', 'google', :provider_id, 'active', NOW())");
    $ins->execute(['email' => $email, 'provider_id' => $googleId]);
    $id = (int)$pdo->lastInsertId();
    $stmt = $pdo->prepare('SELECT id,email,role,store_id,ambassador_id,status FROM users WHERE id=:id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if (!$user || !trustaiCanLogin($user)) {
    $msg = $user ? urlencode(trustaiBlockedStatusMessage($user)) : 'login_failed';
    header('Location: /login.html?error=' . $msg);
    exit;
}

trustaiStartSessionForUser($user);
header('Location: ' . trustaiRoleRedirect($user));
exit;
