<?php
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';

if (!$pdo instanceof PDO) {
    jsonResponse(500, ['ok' => false, 'error' => 'database_unavailable', 'message' => 'Database connection unavailable']);
}

function normalizeStoreDomain(string $input): string
{
    $value = strtolower(trim($input));
    if ($value === '') {
        return '';
    }
    if (!preg_match('#^https?://#i', $value)) {
        $value = 'https://' . $value;
    }
    $host = (string)(parse_url($value, PHP_URL_HOST) ?? '');
    if ($host === '') {
        $host = $value;
    }
    return trim($host, " /\t\n\r\0\x0B");
}

function appDebugEnabled(): bool
{
    $value = strtolower(trim((string)(getenv('APP_DEBUG') ?: ($_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? ''))));
    return in_array($value, ['1', 'true', 'yes', 'on'], true);
}

$body = readJsonBody();
$name = trim((string)($body['name'] ?? ''));
$email = strtolower(trim((string)($body['email'] ?? '')));
$phone = trim((string)($body['phone'] ?? ''));
$partner = trim((string)($body['partner'] ?? ''));
$applicantType = trim((string)($body['applicant_type'] ?? 'privat'));
$storeId = (int)($body['store_id'] ?? 0);
$storeUrlOrDomain = trim((string)($body['store_url'] ?? $body['store_domain'] ?? $body['domain'] ?? $partner));
$storeDomain = normalizeStoreDomain($storeUrlOrDomain);
$debugEnabled = appDebugEnabled();

if ($name === '' || $email === '' || $phone === '') {
    jsonResponse(400, ['ok' => false, 'error' => 'name_email_phone_required', 'message' => 'Name, email and phone are required']);
}
if ($storeId <= 0 && $storeDomain === '') {
    jsonResponse(400, ['ok' => false, 'error' => 'store_domain_required', 'message' => 'store_url or domain is required']);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(400, ['ok' => false, 'error' => 'invalid_email', 'message' => 'Invalid email address']);
}

if ($storeId > 0) {
    $storeStmt = $pdo->prepare('SELECT id, default_commission_percent, commission_percent FROM stores WHERE id = :id LIMIT 1');
    $storeStmt->execute(['id' => $storeId]);
} else {
    $storeStmt = $pdo->prepare('SELECT id, default_commission_percent, commission_percent FROM stores WHERE LOWER(TRIM(TRAILING "/" FROM domain)) = :domain OR LOWER(TRIM(TRAILING "/" FROM REPLACE(REPLACE(url,"https://",""),"http://",""))) = :domain OR LOWER(TRIM(TRAILING "/" FROM REPLACE(REPLACE(public_url,"https://",""),"http://",""))) = :domain OR LOWER(TRIM(TRAILING "/" FROM shopify_domain)) = :domain LIMIT 1');
    $storeStmt->execute(['domain' => strtolower($storeDomain)]);
}

$store = $storeStmt->fetch(PDO::FETCH_ASSOC);
if (!$store) {
    jsonResponse(404, ['ok' => false, 'error' => 'store_not_found', 'message' => 'No matching store found']);
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

$insertPayload = [
    'store_id' => (int)$store['id'],
    'name' => $name,
    'ambassador_name' => $name,
    'email' => $email,
    'phone' => $phone,
    'code' => $referralCode,
    'referral_code' => $referralCode,
    'status' => 'pending',
    'commission_percent' => (float)($store['default_commission_percent'] ?? $store['commission_percent'] ?? 0),
];

try {
    $insert = $pdo->prepare('INSERT INTO ambassadors (store_id, user_id, name, ambassador_name, email, phone, code, referral_code, status, commission_percent, created_at, approved_at) VALUES (:store_id, NULL, :name, :ambassador_name, :email, :phone, :code, :referral_code, :status, :commission_percent, NOW(), NULL)');
    $insert->execute($insertPayload);
} catch (Throwable $e) {
    $response = ['ok' => false, 'error' => 'create_application_failed', 'message' => 'Unable to create ambassador application'];
    if ($debugEnabled) {
        $response['debug'] = [
            'normalized_store_domain' => $storeDomain,
            'submitted_store_url' => $storeUrlOrDomain,
            'matched_store_id' => isset($store['id']) ? (int)$store['id'] : null,
            'pdo_error' => $e->getMessage(),
            'insert_payload_keys' => array_keys($insertPayload),
        ];
    }
    jsonResponse(500, $response);
}

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
