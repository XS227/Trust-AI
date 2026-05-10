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
if (!in_array($role, ['ambassador', 'store_admin'], true)) {
    jsonResponse(422, ['ok' => false, 'error' => 'invalid_role']);
}

$currentRole = strtolower((string)($user['role'] ?? ''));
if ($currentRole !== '' && $currentRole !== $role) {
    jsonResponse(409, ['ok' => false, 'error' => 'role_already_set']);
}

$stmt = $pdo->prepare('UPDATE users SET role = :role, updated_at = NOW() WHERE id = :id');
$stmt->execute(['role' => $role, 'id' => (int)$user['id']]);

$_SESSION['role'] = $role;
if (isset($_SESSION['trustai_user']) && is_array($_SESSION['trustai_user'])) {
    $_SESSION['trustai_user']['role'] = $role;
}
unset($_SESSION['vipps_pending_user_id']);

$redirect = $role === 'ambassador'
    ? '/ambassador-signup.html?vipps=1'
    : '/store-signup.html?vipps=1';

jsonResponse(200, ['ok' => true, 'role' => $role, 'redirect' => $redirect]);
