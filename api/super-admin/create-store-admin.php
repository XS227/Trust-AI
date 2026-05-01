<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';
requireRole('super_admin');

try {
    $body = readJsonBody();
    $name = trim((string)($body['name'] ?? ''));
    $email = strtolower(trim((string)($body['email'] ?? '')));
    $phone = trim((string)($body['phone'] ?? ''));
    $password = (string)($body['password'] ?? '');
    $storeId = (int)($body['store_id'] ?? 0);

    if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $storeId <= 0 || strlen($password) < 8) {
        jsonResponse(400, ['ok' => false, 'error' => 'validation_failed', 'message' => 'Missing or invalid required fields.']);
    }

    $storeStmt = $pdo->prepare('SELECT id FROM stores WHERE id = :id LIMIT 1');
    $storeStmt->execute(['id' => $storeId]);
    if (!$storeStmt->fetch(PDO::FETCH_ASSOC)) {
        jsonResponse(404, ['ok' => false, 'error' => 'store_not_found', 'message' => 'Selected store was not found.']);
    }

    $existingStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $existingStmt->execute(['email' => $email]);
    if ($existingStmt->fetch(PDO::FETCH_ASSOC)) {
        jsonResponse(409, ['ok' => false, 'error' => 'email_in_use', 'message' => 'Email is already in use.']);
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    if (!is_string($passwordHash) || $passwordHash === '') {
        jsonResponse(500, ['ok' => false, 'error' => 'password_hash_failed', 'message' => 'Failed to secure password.']);
    }

    $pdo->beginTransaction();

    $insertStmt = $pdo->prepare('INSERT INTO users (email, password_hash, role, status, store_id, ambassador_id, created_at, updated_at) VALUES (:email, :password_hash, :role, :status, :store_id, NULL, NOW(), NOW())');
    $insertStmt->execute([
        'email' => $email,
        'password_hash' => $passwordHash,
        'role' => 'store_admin',
        'status' => 'active',
        'store_id' => $storeId,
    ]);
    $userId = (int)$pdo->lastInsertId();

    $ownerStmt = $pdo->prepare('UPDATE stores SET owner_user_id = :owner_user_id, store_admin_user_id = :store_admin_user_id, contact_name = :contact_name, contact_email = :contact_email, contact_phone = :contact_phone, updated_at = NOW() WHERE id = :id');
    $ownerStmt->execute([
        'owner_user_id' => $userId,
        'store_admin_user_id' => $userId,
        'contact_name' => $name,
        'contact_email' => $email,
        'contact_phone' => $phone !== '' ? $phone : null,
        'id' => $storeId,
    ]);

    $pdo->commit();

    jsonResponse(200, ['ok' => true, 'message' => 'Store admin created.', 'data' => ['user_id' => $userId, 'store_id' => $storeId]]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('create-store-admin failed: ' . $e->getMessage());
    jsonResponse(500, ['ok' => false, 'error' => 'create_store_admin_failed', 'message' => 'Unable to create store admin at this time.']);
}
