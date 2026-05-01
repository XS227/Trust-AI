<?php
declare(strict_types=1);

require_once __DIR__ . '/_auth_common.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    jsonResponse(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

if (!$pdo instanceof PDO) {
    jsonResponse(500, ['ok' => false, 'error' => 'database_unavailable']);
}

function ensurePasswordHashColumn(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'password_hash'");
    $exists = (int)($stmt->fetch()['c'] ?? 0) > 0;

    if (!$exists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NULL AFTER email");
    }

    $checked = true;
}


function ensureStatusColumn(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'status'");
    $exists = (int)($stmt->fetch()['c'] ?? 0) > 0;

    if (!$exists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status VARCHAR(40) NOT NULL DEFAULT 'active' AFTER role");
    }

    $checked = true;
}

$body = readJsonBody();
$email = strtolower(trim((string)($body['email'] ?? $_POST['email'] ?? '')));
$password = (string)($body['password'] ?? $_POST['password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    jsonResponse(422, ['ok' => false, 'error' => 'invalid_input']);
}

ensurePasswordHashColumn($pdo);
ensureStatusColumn($pdo);

$stmt = $pdo->prepare('SELECT id, email, role, status, password_hash FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || empty($user['password_hash']) || !password_verify($password, (string)$user['password_hash'])) {
    jsonResponse(401, ['ok' => false, 'error' => 'invalid_credentials']);
}

if (!trustaiCanLogin($user)) {
    jsonResponse(403, ['ok' => false, 'error' => 'account_inactive', 'message' => trustaiBlockedStatusMessage($user)]);
}

trustaiStartSessionForUser($user);

// Keep legacy and trustai-prefixed session keys explicitly in login flow.
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['email'] = (string)$user['email'];
$_SESSION['role'] = (string)($user['role'] ?? '');
$_SESSION['trustai_user_id'] = (int)$user['id'];
$_SESSION['trustai_user_email'] = (string)$user['email'];

$role = (string)($user['role'] ?? '');
jsonResponse(200, ['ok' => true, 'role' => $role, 'redirect' => trustaiRoleRedirect($user)]);
