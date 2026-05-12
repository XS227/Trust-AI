<?php
declare(strict_types=1);

require_once __DIR__ . '/auth/_auth_common.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    jsonResponse(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

if (!$pdo instanceof PDO) {
    jsonResponse(500, ['ok' => false, 'error' => 'database_unavailable']);
}

$body = readJsonBody();
$storeName = trim((string)($body['store_name'] ?? ''));
$storeUrl = trim((string)($body['store_url'] ?? ''));
$platform = trim((string)($body['platform'] ?? 'Shopify'));
$contactName = trim((string)($body['name'] ?? ''));
$contactEmail = strtolower(trim((string)($body['email'] ?? '')));
$contactPhone = trim((string)($body['phone'] ?? ''));
$password = (string)($body['password'] ?? '');

if ($storeName === '' || $storeUrl === '') {
    jsonResponse(422, ['ok' => false, 'error' => 'missing_fields', 'message' => 'Butikknavn og URL er påkrevd.']);
}

$domain = preg_replace('#^https?://#i', '', rtrim($storeUrl, '/'));
$domain = preg_replace('#/.*$#', '', (string)$domain);
if ($domain === '') {
    jsonResponse(422, ['ok' => false, 'error' => 'invalid_domain', 'message' => 'Ugyldig domene.']);
}

$existingUser = null;
$sessionUserId = (int)($_SESSION['trustai_user_id'] ?? $_SESSION['user_id'] ?? 0);
if ($sessionUserId > 0) {
    $stmt = $pdo->prepare('SELECT id, email, role, store_id, ambassador_id FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $sessionUserId]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if ($existingUser) {
    if (!empty($existingUser['store_id'])) {
        jsonResponse(409, ['ok' => false, 'error' => 'already_has_store', 'message' => 'Du har allerede en butikk knyttet til kontoen.']);
    }
    $contactEmail = strtolower((string)$existingUser['email']);
} else {
    if (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(422, ['ok' => false, 'error' => 'invalid_email', 'message' => 'Ugyldig e-postadresse.']);
    }
    if (strlen($password) < 8) {
        jsonResponse(422, ['ok' => false, 'error' => 'password_too_short', 'message' => 'Passord må være minst 8 tegn.']);
    }
    $check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $check->execute(['email' => $contactEmail]);
    if ($check->fetch(PDO::FETCH_ASSOC)) {
        jsonResponse(409, ['ok' => false, 'error' => 'email_in_use', 'message' => 'E-posten er allerede i bruk. Logg inn for å fortsette.']);
    }
}

$dupDomain = $pdo->prepare('SELECT id FROM stores WHERE domain = :domain LIMIT 1');
$dupDomain->execute(['domain' => $domain]);
if ($dupDomain->fetch(PDO::FETCH_ASSOC)) {
    jsonResponse(409, ['ok' => false, 'error' => 'domain_in_use', 'message' => 'Domenet er allerede registrert.']);
}

try {
    $pdo->beginTransaction();

    $storeStmt = $pdo->prepare(
        'INSERT INTO stores
            (name, domain, url, public_url, platform, status,
             commission_percent, default_commission_percent,
             contact_name, contact_email, contact_phone,
             created_at, updated_at)
         VALUES
            (:name,:domain,:url,:public_url,:platform,"pending",
             20.00,20.00,
             :cname,:cemail,:cphone,
             NOW(),NOW())'
    );
    $storeStmt->execute([
        'name' => $storeName,
        'domain' => $domain,
        'url' => $storeUrl,
        'public_url' => $storeUrl,
        'platform' => $platform !== '' ? $platform : 'Shopify',
        'cname' => $contactName !== '' ? $contactName : null,
        'cemail' => $contactEmail,
        'cphone' => $contactPhone !== '' ? $contactPhone : null,
    ]);
    $storeId = (int)$pdo->lastInsertId();

    if ($existingUser) {
        $userId = (int)$existingUser['id'];
        $upd = $pdo->prepare('UPDATE users SET role = "store_admin", store_id = :sid, updated_at = NOW() WHERE id = :id');
        $upd->execute(['sid' => $storeId, 'id' => $userId]);
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $userStmt = $pdo->prepare(
            'INSERT INTO users (email, password_hash, role, status, store_id, phone, name, created_at, updated_at)
             VALUES (:email, :hash, "store_admin", "active", :sid, :phone, :name, NOW(), NOW())'
        );
        $userStmt->execute([
            'email' => $contactEmail,
            'hash' => $passwordHash,
            'sid' => $storeId,
            'phone' => $contactPhone !== '' ? $contactPhone : null,
            'name' => $contactName !== '' ? $contactName : null,
        ]);
        $userId = (int)$pdo->lastInsertId();
    }

    $pdo->prepare('UPDATE stores SET owner_user_id = :uid, store_admin_user_id = :uid, updated_at = NOW() WHERE id = :sid')
        ->execute(['uid' => $userId, 'sid' => $storeId]);

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('store-signup failed: ' . $e->getMessage());
    $sqlState = (string)($e->errorInfo[0] ?? '');
    if ($sqlState === '23000') {
        jsonResponse(409, ['ok' => false, 'error' => 'duplicate_conflict', 'message' => 'E-post eller domene finnes allerede.']);
    }
    jsonResponse(500, ['ok' => false, 'error' => 'create_failed', 'message' => 'Kunne ikke fullføre registreringen.']);
}

$stmt = $pdo->prepare('SELECT id, email, role, status, store_id, ambassador_id FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user) {
    trustaiStartSessionForUser($user);
}

jsonResponse(200, [
    'ok' => true,
    'store_id' => $storeId,
    'user_id' => $userId,
    'redirect' => '/onboarding.html',
]);
