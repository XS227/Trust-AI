<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';

$user = requireLogin();
if (!in_array($user['role'], ['store_admin', 'super_admin'], true)) {
    jsonResponse(403, ['ok' => false, 'error' => 'forbidden_role']);
}

$body = readJsonBody();
$storeId = (int)($body['store_id'] ?? $user['store_id'] ?? 0);
if (!isSuperAdmin($user) && $storeId !== (int)$user['store_id']) {
    jsonResponse(403, ['ok' => false, 'error' => 'store_mismatch']);
}

$name = trim((string)($body['name'] ?? ''));
$email = strtolower(trim((string)($body['email'] ?? '')));
$commission = (float)($body['commission_percent'] ?? 0);
$status = trim((string)($body['status'] ?? 'approved'));
$pass = (string)($body['temporary_password'] ?? '');
$phone = trim((string)($body['phone'] ?? ''));

if ($name === '' || $email === '' || $pass === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $commission < 0 || $commission > 100) {
    jsonResponse(400, ['ok' => false, 'error' => 'validation']);
}
if (!in_array($status, ['approved', 'pending', 'paused', 'rejected'], true)) {
    jsonResponse(400, ['ok' => false, 'error' => 'invalid_status']);
}

$ref = trim((string)($body['referral_code'] ?? ''));
if ($ref === '') {
    $ref = strtolower(substr((string)preg_replace('/[^a-z0-9]/i', '', $name), 0, 8)) . substr(bin2hex(random_bytes(3)), 0, 6);
}

$pdo->beginTransaction();
try {
    $pdo->prepare('INSERT INTO users (email, password_hash, user_role, user_type, store_id, must_change_password, created_at, updated_at) VALUES (:email, :ph, :role, :user_type, :store_id, :must_change_password, NOW(), NOW())')
        ->execute([
            'email' => $email,
            'ph' => password_hash($pass, PASSWORD_DEFAULT),
            'role' => 'ambassador',
            'user_type' => 'external',
            'store_id' => $storeId,
            'must_change_password' => !empty($body['must_change_password']) ? 1 : 0,
        ]);

    $uid = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO ambassadors (store_id, user_id, name, ambassador_name, email, phone, code, referral_code, status, commission_percent, created_at, approved_at, updated_at) VALUES (:store_id, :user_id, :name, :ambassador_name, :email, :phone, :code, :referral_code, :status, :commission_percent, NOW(), IF(:status="approved",NOW(),NULL), NOW())')
        ->execute([
            'store_id' => $storeId,
            'user_id' => $uid,
            'name' => $name,
            'ambassador_name' => $name,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'code' => $ref,
            'referral_code' => $ref,
            'status' => $status,
            'commission_percent' => $commission,
        ]);

    $aid = (int)$pdo->lastInsertId();
    $pdo->prepare('UPDATE users SET ambassador_id = :aid WHERE id = :uid')->execute(['aid' => $aid, 'uid' => $uid]);

    $pdo->commit();
    jsonResponse(201, ['ok' => true, 'ambassador' => ['id' => $aid, 'store_id' => $storeId, 'user_id' => $uid, 'name' => $name, 'email' => $email, 'phone' => $phone, 'referral_code' => $ref, 'status' => $status, 'commission_percent' => $commission]]);
} catch (Throwable $e) {
    $pdo->rollBack();
    jsonResponse(400, ['ok' => false, 'error' => 'create_ambassador_failed', 'message' => $e->getMessage()]);
}
