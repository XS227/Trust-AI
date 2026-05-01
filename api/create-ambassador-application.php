<?php
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';

$body = readJsonBody();
$name = trim((string)($body['name'] ?? ''));
$email = strtolower(trim((string)($body['email'] ?? '')));
$phone = trim((string)($body['phone'] ?? ''));
$partner = trim((string)($body['partner'] ?? ''));
$applicantType = trim((string)($body['applicant_type'] ?? 'privat'));
$storeId = (int)($body['store_id'] ?? 0);
$storeDomainRaw = strtolower(trim((string)($body['store_domain'] ?? $partner)));
$storeDomain = preg_replace('#^https?://#', '', $storeDomainRaw);
$storeDomain = trim((string)$storeDomain, " /\t\n\r\0\x0B");

if ($name === '' || $email === '' || $phone === '') {
    jsonResponse(400, ['ok' => false, 'error' => 'name_email_phone_required']);
}
if ($storeId <= 0 && $storeDomain === '') {
    jsonResponse(400, ['ok' => false, 'error' => 'store_domain_required']);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(400, ['ok' => false, 'error' => 'invalid_email']);
}

if ($storeId > 0) {
    $storeStmt = $pdo->prepare('SELECT id, default_commission_percent FROM stores WHERE id = :id LIMIT 1');
    $storeStmt->execute(['id' => $storeId]);
} else {
    $storeStmt = $pdo->prepare('SELECT id, default_commission_percent FROM stores WHERE LOWER(REPLACE(REPLACE(domain,"https://",""),"http://","")) = :domain OR LOWER(REPLACE(REPLACE(public_url,"https://",""),"http://","")) = :domain LIMIT 1');
    $storeStmt->execute(['domain' => strtolower($storeDomain)]);
}

$store = $storeStmt->fetch();
if (!$store) {
    jsonResponse(404, ['ok' => false, 'error' => 'store_not_found']);
}

$dupStmt = $pdo->prepare('SELECT id, status FROM ambassadors WHERE store_id = :store_id AND LOWER(email) = :email ORDER BY id DESC LIMIT 1');
$dupStmt->execute(['store_id' => (int)$store['id'], 'email' => $email]);
$existing = $dupStmt->fetch(PDO::FETCH_ASSOC);
if ($existing && in_array((string)$existing['status'], ['pending', 'approved', 'paused'], true)) {
    jsonResponse(409, ['ok' => false, 'error' => 'duplicate_application', 'message' => 'Du har allerede en søknad for denne butikken.', 'existing_status' => (string)$existing['status']]);
}

$codeBase = strtolower((string)preg_replace('/[^a-z0-9]/i', '', $name));
$codeBase = substr($codeBase ?: 'amb', 0, 8);
$referralCode = $codeBase . substr(md5($email . microtime(true)), 0, 6);

$insert = $pdo->prepare('INSERT INTO ambassadors (store_id, user_id, name, email, phone, referral_code, status, commission_percent, created_at, approved_at) VALUES (:store_id, NULL, :name, :email, :phone, :referral_code, :status, :commission_percent, NOW(), NULL)');
$insert->execute([
    'store_id' => (int)$store['id'],
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'referral_code' => $referralCode,
    'status' => 'pending',
    'commission_percent' => (float)$store['default_commission_percent'],
]);

$ambassadorId = (int)$pdo->lastInsertId();

jsonResponse(200, [
    'ok' => true,
    'application_id' => $ambassadorId,
    'status' => 'pending',
    'state' => 'soknad',
    'store_id' => (int)$store['id'],
    'partner' => $partner,
    'applicant_type' => $applicantType,
]);
