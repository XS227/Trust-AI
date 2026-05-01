<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth_common.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    jsonResponse(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

$body = readJsonBody();
$token = trim((string)($body['token'] ?? ''));
$password = (string)($body['password'] ?? '');

if ($token === '' || strlen($password) < 8 || !$pdo instanceof PDO) {
    jsonResponse(422, ['ok' => false, 'error' => 'invalid_input']);
}

$tokenHash = hash('sha256', $token);
$stmt = $pdo->prepare('SELECT pr.id, pr.user_id FROM password_resets pr WHERE pr.token_hash=:token_hash AND pr.used_at IS NULL AND pr.expires_at > NOW() ORDER BY pr.id DESC LIMIT 1');
$stmt->execute(['token_hash' => $tokenHash]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    jsonResponse(400, ['ok' => false, 'error' => 'invalid_or_expired_token']);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$pdo->prepare('UPDATE users SET password_hash=:hash WHERE id=:id')->execute(['hash' => $hash, 'id' => (int)$row['user_id']]);
$pdo->prepare('UPDATE password_resets SET used_at=NOW() WHERE id=:id')->execute(['id' => (int)$row['id']]);
jsonResponse(200, ['ok' => true]);
