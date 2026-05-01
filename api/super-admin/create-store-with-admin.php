<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../_auth.php';

try {
    $currentUser = requireRole('super_admin');

    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    if (!is_array($data)) {
        jsonResponse(400, ['ok' => false, 'error' => 'invalid_json', 'message' => 'Ugyldig JSON i forespørsel.']);
    }

    $storeName  = trim((string)($data['store_name'] ?? ''));
    $storeUrl   = trim((string)($data['store_url'] ?? ''));
    $platform   = trim((string)($data['platform'] ?? ''));
    $commission = (float)($data['commission'] ?? 20.0);
    $name       = trim((string)($data['name'] ?? ''));
    $email      = trim((string)($data['email'] ?? ''));
    $phone      = trim((string)($data['phone'] ?? ''));
    $password   = (string)($data['password'] ?? '');

    if (!$storeName || !$storeUrl || !$email || !$password) {
        jsonResponse(400, ['ok'=>false,'error'=>'missing_fields','message'=>'Butikknavn, URL, e-post og passord er påkrevd.']);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(400, ['ok'=>false,'error'=>'invalid_email','message'=>'Ugyldig e-postadresse.']);
    }
    if (strlen($password) < 8) {
        jsonResponse(400, ['ok'=>false,'error'=>'password_too_short','message'=>'Passord må være minst 8 tegn.']);
    }

    $existingStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $existingStmt->execute(['email' => $email]);
    if ($existingStmt->fetch(PDO::FETCH_ASSOC)) {
        jsonResponse(409, ['ok'=>false,'error'=>'email_in_use','message'=>'E-posten er allerede i bruk.']);
    }

    $domain = preg_replace('#^https?://#i', '', rtrim($storeUrl, '/'));
    if (!$domain) {
        jsonResponse(400, ['ok'=>false,'error'=>'invalid_domain','message'=>'Kunne ikke lese domenet fra URL.']);
    }

    $domainStmt = $pdo->prepare('SELECT id FROM stores WHERE domain = :domain LIMIT 1');
    $domainStmt->execute(['domain' => $domain]);
    if ($domainStmt->fetch(PDO::FETCH_ASSOC)) {
        jsonResponse(409, ['ok'=>false,'error'=>'domain_in_use','message'=>'Domenet er allerede registrert.']);
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    if (!$passwordHash) {
        throw new RuntimeException('Kunne ikke hashe passord.');
    }

    $pdo->beginTransaction();

    $storeStmt = $pdo->prepare('INSERT INTO stores (name, domain, url, public_url, platform, status, commission_percent, default_commission_percent, contact_name, contact_email, contact_phone, created_at, updated_at) VALUES (:name,:domain,:url,:public_url,:platform,"active",:comm,:comm,:cname,:cemail,:cphone,NOW(),NOW())');
    $storeStmt->execute([
        'name' => $storeName,
        'domain' => $domain,
        'url' => $storeUrl,
        'public_url' => $storeUrl,
        'platform' => $platform !== '' ? $platform : 'Shopify',
        'comm' => $commission,
        'cname' => $name !== '' ? $name : null,
        'cemail' => $email,
        'cphone' => $phone !== '' ? $phone : null,
    ]);
    $storeId = (int)$pdo->lastInsertId();

    $userStmt = $pdo->prepare('INSERT INTO users (email,password_hash,user_role,user_type,status,store_id,ambassador_id,must_change_password,created_at,updated_at) VALUES (:email,:hash,"store_admin","internal","active",:store_id,NULL,0,NOW(),NOW())');
    $userStmt->execute(['email'=>$email,'hash'=>$passwordHash,'store_id'=>$storeId]);
    $userId = (int)$pdo->lastInsertId();

    $pdo->prepare('UPDATE stores SET owner_user_id=:uid,store_admin_user_id=:uid,updated_at=NOW() WHERE id=:id')
        ->execute(['uid'=>$userId,'id'=>$storeId]);

    $pdo->commit();
    jsonResponse(200, ['ok'=>true,'store_id'=>$storeId,'user_id'=>$userId]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $message = 'Kunne ikke opprette butikk.';
    $code = 500;
    $error = 'create_failed';

    if ($e instanceof PDOException) {
        $sqlState = (string)($e->errorInfo[0] ?? '');
        if ($sqlState === '23000') {
            $code = 409;
            $error = 'duplicate_conflict';
            $message = 'E-post eller domene finnes allerede.';
        }
    }

    error_log('create-store-with-admin failed: ' . $e->getMessage());

    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['ok' => false, 'error' => $error, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}
