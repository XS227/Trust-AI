<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    jsonResponse(405, ['ok' => false, 'error' => 'method_not_allowed']);
}
$user = requireLogin();
$body = readJsonBody();
$current = (string)($body['current_password'] ?? '');
$new = (string)($body['new_password'] ?? '');
$confirm = (string)($body['confirm_password'] ?? '');
if ($new === '' || strlen($new) < 8 || $new !== $confirm) {
    jsonResponse(422, ['ok' => false, 'error' => 'invalid_new_password']);
}
$stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => (int)$user['id']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row || empty($row['password_hash']) || !password_verify($current, (string)$row['password_hash'])) {
    jsonResponse(401, ['ok' => false, 'error' => 'invalid_credentials']);
}
$hash = password_hash($new, PASSWORD_DEFAULT);
$up = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
$up->execute(['password_hash' => $hash, 'id' => (int)$user['id']]);
jsonResponse(200, ['ok' => true]);
