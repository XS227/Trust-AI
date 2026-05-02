<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/_auth.php';
if (!$pdo instanceof PDO) {
    jsonResponse(500, ['ok' => false, 'error' => 'database_unavailable', 'message' => 'Database connection unavailable']);
}
function normalizeStoreDomain(string $input): string
{
    $value = strtolower(trim($input));
    if ($value === '') return '';
    if (!preg_match('#^https?://#i', $value)) $value = 'https://' . $value;
    $host = (string)(parse_url($value, PHP_URL_HOST) ?? '');
    if ($host === '') $host = $value;
    return trim($host, " /\t\n\r\0\x0B");
}
function appDebugEnabled(): bool
{
    $value = strtolower(trim((string)(getenv('APP_DEBUG') ?: ($_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? ''))));
    return in_array($value, ['1', 'true', 'yes', 'on'], true);
}
try {
    $body = readJsonBody();
    $name = trim((string)($body['name'] ?? ''));
    $email = strtolower(trim((string)($body['email'] ?? '')));
    $phone = trim((string)($body['phone'] ?? ''));
    $password = (string)($body['password'] ?? '');
    $partner = trim((string)($body['partner'] ?? ''));
    $applicantType = trim((string)($body['applicant_type'] ?? 'privat'));
    $storeId = (int)($body['store_id'] ?? 0);
    $storeUrlOrDomain = trim((string)($body['store_url'] ?? $body['store_domain'] ?? $body['domain'] ?? $partner));
    $storeDomain = normalizeStoreDomain($storeUrlOrDomain);
    $debugEnabled = appDebugEnabled();

    if ($name === '' || $email === '' || $phone === '') {
        jsonResponse(400, ['ok' => false, 'error' => 'name_email_phone_required', 'message' => 'Navn, e-post og telefon er påkrevd']);
    }
    if ($password === '' || strlen($password) < 8) {
        jsonResponse(400, ['ok' => false, 'error' => 'password_too_short', 'message' => 'Passord må være minst 8 tegn']);
    }
    if ($storeId <= 0 && $storeDomain === '') {
        jsonResponse(400, ['ok' => false, 'error' => 'store_domain_required', 'message' => 'store_url eller domain er påkrevd']);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(400, ['ok' => false, 'error' => 'invalid_email', 'message' => 'Ugyldig e-postadresse']);
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
        jsonResponse(404, ['ok' => false, 'error' => 'store_not_found', 'message' => 'Ingen matchende butikk funnet']);
    }

    // Sjekk duplikat ambassador-søknad
    $dupStmt = $pdo->prepare('SELECT id, status FROM ambassadors WHERE store_id = :store_id AND LOWER(email) = :email ORDER BY id DESC LIMIT 1');
    $dupStmt->execute(['store_id' => (int)$store['id'], 'email' => $email]);
    $existing = $dupStmt->fetch(PDO::FETCH_ASSOC);
    if ($existing && in_array((string)$existing['status'], ['pending', 'approved', 'paused'], true)) {
        jsonResponse(409, ['ok' => false, 'error' => 'duplicate_application', 'message' => 'Du har allerede en søknad for denne butikken.', 'existing_status' => (string)$existing['status']]);
    }

    // Sjekk om e-post allerede brukes som user
    $userDupStmt = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = :email LIMIT 1');
    $userDupStmt->execute(['email' => $email]);
    if ($userDupStmt->fetch()) {
        jsonResponse(409, ['ok' => false, 'error' => 'email_in_use', 'message' => 'E-posten er allerede registrert. Bruk login eller en annen e-post.']);
    }

    $codeBase = strtolower((string)preg_replace('/[^a-z0-9]/i', '', $name));
    $codeBase = substr($codeBase ?: 'amb', 0, 8);
    $referralCode = $codeBase . substr(md5($email . microtime(true)), 0, 6);
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();

        // 1. Opprett ambassador-rad
        $insert = $pdo->prepare('INSERT INTO ambassadors (store_id, user_id, name, ambassador_name, email, phone, code, referral_code, status, commission_percent, created_at, approved_at) VALUES (:store_id, NULL, :name, :ambassador_name, :email, :phone, :code, :referral_code, :status, :commission_percent, NOW(), NULL)');
        $insert->execute([
            'store_id' => (int)$store['id'],
            'name' => $name,
            'ambassador_name' => $name,
            'email' => $email,
            'phone' => $phone,
            'code' => $referralCode,
            'referral_code' => $referralCode,
            'status' => 'pending',
            'commission_percent' => (float)($store['default_commission_percent'] ?? $store['commission_percent'] ?? 0),
        ]);
        $ambassadorId = (int)$pdo->lastInsertId();

        // 2. Opprett user-konto med passord
        $userInsert = $pdo->prepare('INSERT INTO users (email, password_hash, role, user_role, user_type, status, store_id, ambassador_id, must_change_password, created_at, updated_at) VALUES (:email, :hash, "ambassador", "ambassador", "internal", "active", :store_id, :ambassador_id, 0, NOW(), NOW())');
        $userInsert->execute([
            'email' => $email,
            'hash' => $passwordHash,
            'store_id' => (int)$store['id'],
            'ambassador_id' => $ambassadorId,
        ]);
        $userId = (int)$pdo->lastInsertId();

        // 3. Link ambassador.user_id tilbake til user
        $pdo->prepare('UPDATE ambassadors SET user_id = :uid WHERE id = :id')->execute(['uid' => $userId, 'id' => $ambassadorId]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $response = ['ok' => false, 'error' => 'create_application_failed', 'message' => 'Kunne ikke opprette søknad'];
        if ($debugEnabled) {
            $response['debug'] = ['pdo_error' => $e->getMessage()];
        }
        error_log('create-ambassador-application failed: ' . $e->getMessage());
        jsonResponse(500, $response);
    }

    jsonResponse(200, [
        'ok' => true,
        'application_id' => $ambassadorId,
        'user_id' => $userId,
        'status' => 'pending',
        'state' => 'soknad',
        'store_id' => (int)$store['id'],
        'partner' => $partner,
        'applicant_type' => $applicantType,
        'message' => 'Søknad mottatt. Du kan logge inn så snart butikken godkjenner deg.',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'fatal_error', 'message' => $e->getMessage()]);
    exit;
}
