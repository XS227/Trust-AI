<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';
requireRole('super_admin');

$body = readJsonBody();
$name = trim((string)($body['name'] ?? ''));
$email = strtolower(trim((string)($body['email'] ?? '')));
$phone = trim((string)($body['phone'] ?? ''));
$password = (string)($body['password'] ?? '');
$storeId = (int)($body['store_id'] ?? 0);

if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $storeId <= 0 || strlen($password) < 8) {
    jsonResponse(400, ['ok' => false, 'error' => 'validation_failed']);
}

$storeStmt = $pdo->prepare('SELECT id FROM stores WHERE id = :id LIMIT 1');
$storeStmt->execute(['id' => $storeId]);
if (!$storeStmt->fetch(PDO::FETCH_ASSOC)) {
    jsonResponse(404, ['ok' => false, 'error' => 'store_not_found']);
}

$existingStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$existingStmt->execute(['email' => $email]);
if ($existingStmt->fetch(PDO::FETCH_ASSOC)) {
    jsonResponse(409, ['ok' => false, 'error' => 'email_in_use']);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$insertStmt = $pdo->prepare('INSERT INTO users (email, password_hash, role, store_id, ambassador_id, created_at) VALUES (:email, :password_hash, :role, :store_id, NULL, NOW())');
$insertStmt->execute(['email' => $email, 'password_hash' => $passwordHash, 'role' => 'store_admin', 'store_id' => $storeId]);
$userId = (int)$pdo->lastInsertId();

$ownerStmt = $pdo->prepare('UPDATE stores SET owner_user_id = :owner_user_id, contact_name = :contact_name, contact_email = :contact_email, contact_phone = :contact_phone WHERE id = :id');
$ownerStmt->execute(['owner_user_id' => $userId, 'contact_name' => $name, 'contact_email' => $email, 'contact_phone' => $phone !== '' ? $phone : null, 'id' => $storeId]);

jsonResponse(200, ['ok' => true, 'data' => ['user_id' => $userId, 'store_id' => $storeId]]);
