<?php
declare(strict_types=1);

require_once __DIR__ . '/../_auth.php';
$currentUser = requireRole('super_admin');

$data = json_decode(file_get_contents('php://input'), true);

$storeName  = trim($data['store_name']  ?? '');
$storeUrl   = trim($data['store_url']   ?? '');
$platform   = trim($data['platform']    ?? '');
$commission = (float)($data['commission'] ?? 20.0);
$name       = trim($data['name']        ?? '');
$email      = trim($data['email']       ?? '');
$phone      = trim($data['phone']       ?? '');
$password   = $data['password']         ?? '';

if (!$storeName || !$storeUrl || !$email || !$password) {
    jsonResponse(400, ['ok'=>false,'error'=>'missing_fields','message'=>'Butikknavn, URL, e-post og passord er påkrevd.']);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(400, ['ok'=>false,'error'=>'invalid_email','message'=>'Ugyldig e-postadresse.']);
}
if (strlen($password) < 8) {
    jsonResponse(400, ['ok'=>false,'error'=>'password_too_short','message'=>'Passord må være minst 8 tegn.']);
}

try {
    $existingStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $existingStmt->execute(['email' => $email]);
    if ($existingStmt->fetch(PDO::FETCH_ASSOC)) {
        jsonResponse(409, ['ok'=>false,'error'=>'email_in_use','message'=>'E-posten er allerede i bruk.']);
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $pdo->beginTransaction();

    $domain = preg_replace('#^https?://#', '', rtrim($storeUrl, '/'));
    $storeStmt = $pdo->prepare('INSERT INTO stores (name, domain, url, public_url, platform, status, commission_percent, default_commission_percent, contact_name, contact_email, contact_phone, created_at) VALUES (:name,:domain,:url,:url,:platform,"active",:comm,:comm,:cname,:cemail,:cphone,NOW())');
    $storeStmt->execute([
        'name'=>$storeName,'domain'=>$domain,'url'=>$storeUrl,
        'platform'=>$platform?:null,'comm'=>$commission,
        'cname'=>$name?:null,'cemail'=>$email,'cphone'=>$phone?:null
    ]);
    $storeId = (int)$pdo->lastInsertId();

    $userStmt = $pdo->prepare('INSERT INTO users (email,password_hash,role,status,store_id,ambassador_id,created_at,updated_at) VALUES (:email,:hash,"store_admin","active",:store_id,NULL,NOW(),NOW())');
    $userStmt->execute(['email'=>$email,'hash'=>$passwordHash,'store_id'=>$storeId]);
    $userId = (int)$pdo->lastInsertId();

    $pdo->prepare('UPDATE stores SET owner_user_id=:uid,store_admin_user_id=:uid,updated_at=NOW() WHERE id=:id')
        ->execute(['uid'=>$userId,'id'=>$storeId]);

    $pdo->commit();
    jsonResponse(200, ['ok'=>true,'message'=>'Butikk og admin opprettet!','data'=>['store_id'=>$storeId,'user_id'=>$userId]]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    error_log('create-store-with-admin failed: '.$e->getMessage());
    jsonResponse(500, ['ok'=>false,'error'=>'create_failed','message'=>'Kunne ikke opprette butikk.']);
}
PHPEOF
