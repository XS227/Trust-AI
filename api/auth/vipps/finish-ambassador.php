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
$storeUrl = trim((string)($body['store_url'] ?? $body['domain'] ?? ''));
$applicantType = trim((string)($body['applicant_type'] ?? 'privat'));

if ($storeUrl === '') {
    jsonResponse(422, ['ok' => false, 'error' => 'store_domain_required', 'message' => 'Butikkdomene er påkrevd.']);
}

$normalizeDomain = static function (string $input): string {
    $value = strtolower(trim($input));
    if ($value === '') return '';
    if (!preg_match('#^https?://#i', $value)) $value = 'https://' . $value;
    $host = (string)(parse_url($value, PHP_URL_HOST) ?? '');
    if ($host === '') $host = $value;
    return trim($host, " /\t\n\r\0\x0B");
};
$storeDomain = $normalizeDomain($storeUrl);
if ($storeDomain === '') {
    jsonResponse(422, ['ok' => false, 'error' => 'invalid_domain']);
}

$storeStmt = $pdo->prepare(
    'SELECT id, default_commission_percent, commission_percent
     FROM stores
     WHERE LOWER(TRIM(TRAILING "/" FROM domain)) = :domain
        OR LOWER(TRIM(TRAILING "/" FROM REPLACE(REPLACE(url,"https://",""),"http://",""))) = :domain
        OR LOWER(TRIM(TRAILING "/" FROM REPLACE(REPLACE(public_url,"https://",""),"http://",""))) = :domain
     LIMIT 1'
);
$storeStmt->execute(['domain' => $storeDomain]);
$store = $storeStmt->fetch(PDO::FETCH_ASSOC);
if (!$store) {
    jsonResponse(404, ['ok' => false, 'error' => 'store_not_found', 'message' => 'Ingen matchende butikk funnet for det domenet.']);
}

$storeId = (int)$store['id'];
$userId = (int)$user['id'];
$email = strtolower((string)$user['email']);

$detail = $pdo->prepare('SELECT phone_number, full_name, name, phone FROM users WHERE id = :id LIMIT 1');
$detail->execute(['id' => $userId]);
$details = $detail->fetch(PDO::FETCH_ASSOC) ?: [];

$displayName = trim((string)($details['full_name'] ?? $details['name'] ?? ''));
if ($displayName === '') {
    $displayName = 'Ambassadør';
}
$phone = trim((string)($details['phone_number'] ?? $details['phone'] ?? ''));

$dupStmt = $pdo->prepare('SELECT id, status FROM ambassadors WHERE store_id = :sid AND (LOWER(email) = :email OR user_id = :uid) ORDER BY id DESC LIMIT 1');
$dupStmt->execute(['sid' => $storeId, 'email' => $email, 'uid' => $userId]);
$existing = $dupStmt->fetch(PDO::FETCH_ASSOC);
if ($existing && in_array((string)$existing['status'], ['pending', 'approved', 'paused'], true)) {
    jsonResponse(409, [
        'ok' => false,
        'error' => 'duplicate_application',
        'message' => 'Du har allerede en søknad for denne butikken.',
        'existing_status' => (string)$existing['status'],
    ]);
}

$codeBase = strtolower((string)preg_replace('/[^a-z0-9]/i', '', $displayName));
$codeBase = substr($codeBase ?: 'amb', 0, 8);
$referralCode = $codeBase . substr(md5($email . microtime(true)), 0, 6);
$commission = (float)($store['default_commission_percent'] ?? $store['commission_percent'] ?? 0);

try {
    $pdo->beginTransaction();

    $insert = $pdo->prepare(
        'INSERT INTO ambassadors
            (store_id, user_id, name, ambassador_name, email, phone, code, referral_code, status, commission_percent, created_at, approved_at)
         VALUES
            (:sid, :uid, :name, :ambassador_name, :email, :phone, :code, :referral_code, :status, :comm, NOW(), NULL)'
    );
    $insert->execute([
        'sid' => $storeId,
        'uid' => $userId,
        'name' => $displayName,
        'ambassador_name' => $displayName,
        'email' => $email,
        'phone' => $phone !== '' ? $phone : null,
        'code' => $referralCode,
        'referral_code' => $referralCode,
        'status' => 'pending',
        'comm' => $commission,
    ]);
    $ambassadorId = (int)$pdo->lastInsertId();

    $pdo->prepare('UPDATE users SET role = "ambassador", store_id = :sid, ambassador_id = :aid, updated_at = NOW() WHERE id = :uid')
        ->execute(['sid' => $storeId, 'aid' => $ambassadorId, 'uid' => $userId]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('vipps finish-ambassador failed: ' . $e->getMessage());
    jsonResponse(500, ['ok' => false, 'error' => 'create_application_failed', 'message' => 'Kunne ikke opprette søknad.']);
}

$_SESSION['role'] = 'ambassador';
$_SESSION['ambassador_id'] = $ambassadorId;
$_SESSION['store_id'] = $storeId;
if (isset($_SESSION['trustai_user']) && is_array($_SESSION['trustai_user'])) {
    $_SESSION['trustai_user']['role'] = 'ambassador';
    $_SESSION['trustai_user']['ambassador_id'] = $ambassadorId;
    $_SESSION['trustai_user']['store_id'] = $storeId;
}

jsonResponse(200, [
    'ok' => true,
    'application_id' => $ambassadorId,
    'store_id' => $storeId,
    'status' => 'pending',
    'message' => 'Søknad mottatt. Du kan logge inn så snart butikken godkjenner deg.',
]);
