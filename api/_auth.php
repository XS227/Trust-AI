<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = null;
$dbBootstrapError = null;
try {
    require_once __DIR__ . '/../inc/db.php';
} catch (Throwable $e) {
    $dbBootstrapError = (string)$e->getMessage();
    error_log('api/_auth.php: DB bootstrap failed: ' . $dbBootstrapError);
}

if (!headers_sent()) {
    header('Access-Control-Allow-Credentials: true');
    if (!empty($_SERVER['HTTP_ORIGIN'])) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
        header('Vary: Origin');
    }
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-Email, X-User-Id');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (!isset($_COOKIE) || !is_array($_COOKIE)) {
    $_COOKIE = [];
}

if (isset($_SERVER['HTTP_COOKIE']) && is_string($_SERVER['HTTP_COOKIE'])) {
    $pairs = array_filter(array_map('trim', explode(';', $_SERVER['HTTP_COOKIE'])));
    foreach ($pairs as $pair) {
        [$k, $v] = array_pad(explode('=', $pair, 2), 2, '');
        $name = trim((string)$k);
        if ($name !== '' && !array_key_exists($name, $_COOKIE)) {
            $_COOKIE[$name] = urldecode((string)$v);
        }
    }
}

function jsonResponse(int $status, array $payload): void
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function readJsonBody(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function getBearerToken(): string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $header, $m)) {
        return trim($m[1]);
    }
    return '';
}

function getCurrentUser(): ?array
{
    global $pdo;

    if (!$pdo instanceof PDO) {
        error_log('api/_auth.php: getCurrentUser called without DB connection');
        return null;
    }

    if (!empty($_SESSION['trustai_user']) && is_array($_SESSION['trustai_user'])) {
        return $_SESSION['trustai_user'];
    }

    $sessionUserId = (int)($_SESSION['trustai_user_id'] ?? 0);
    $sessionEmail = trim((string)($_SESSION['trustai_user_email'] ?? ''));
    $email = trim((string)($_SERVER['HTTP_X_USER_EMAIL'] ?? ''));
    $id = (int)($_SERVER['HTTP_X_USER_ID'] ?? 0);

    if ($id <= 0 && $sessionUserId > 0) {
        $id = $sessionUserId;
    }
    if ($email === '' && $sessionEmail !== '') {
        $email = $sessionEmail;
    }
    if ($email === '' && !empty($_SESSION['vipps_user']) && is_array($_SESSION['vipps_user'])) {
        $email = strtolower(trim((string)($_SESSION['vipps_user']['email'] ?? '')));
    }

    if ($id <= 0 && $email === '') {
        return null;
    }

    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT id, email, role, store_id, ambassador_id, created_at FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
    } else {
        $stmt = $pdo->prepare('SELECT id, email, role, store_id, ambassador_id, created_at FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => strtolower($email)]);
    }

    $user = $stmt->fetch();
    if (!$user) {
        return null;
    }

    $_SESSION['trustai_user'] = $user;
    $_SESSION['trustai_user_id'] = (int)($user['id'] ?? 0);
    $_SESSION['trustai_user_email'] = (string)($user['email'] ?? '');
    return $user;
}

function requireLogin(): array
{
    $user = getCurrentUser();
    if (!$user) {
        jsonResponse(401, ['ok' => false, 'error' => 'login_required']);
    }
    return $user;
}

function requireRole(string $role): array
{
    $user = requireLogin();
    if (($user['role'] ?? '') !== $role) {
        jsonResponse(403, ['ok' => false, 'error' => 'forbidden_role', 'required' => $role]);
    }
    return $user;
}

function isSuperAdmin(?array $user = null): bool
{
    $u = $user ?? getCurrentUser();
    return ($u['role'] ?? '') === 'super_admin';
}

function requireStoreAccess(int $storeId): array
{
    $user = requireLogin();
    if (isSuperAdmin($user)) {
        return $user;
    }

    if (($user['role'] ?? '') !== 'store_admin') {
        jsonResponse(403, ['ok' => false, 'error' => 'store_access_denied']);
    }

    if ((int)($user['store_id'] ?? 0) !== $storeId) {
        jsonResponse(403, ['ok' => false, 'error' => 'store_mismatch']);
    }

    return $user;
}

function requireAmbassadorAccess(int $ambassadorId): array
{
    $user = requireLogin();
    if (isSuperAdmin($user)) {
        return $user;
    }

    if (($user['role'] ?? '') !== 'ambassador' || (int)($user['ambassador_id'] ?? 0) !== $ambassadorId) {
        jsonResponse(403, ['ok' => false, 'error' => 'ambassador_access_denied']);
    }

    return $user;
}
