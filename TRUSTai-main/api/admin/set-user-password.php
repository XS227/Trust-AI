<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') jsonResponse(405, ['ok'=>false,'error'=>'method_not_allowed']);
requireRole('super_admin');
$body=readJsonBody();$userId=(int)($body['user_id']??0);$new=(string)($body['new_password']??'');
if($userId<=0||strlen($new)<8) jsonResponse(422,['ok'=>false,'error'=>'invalid_input']);
$hash=password_hash($new,PASSWORD_DEFAULT);
$pdo->prepare('UPDATE users SET password_hash=:password_hash WHERE id=:id')->execute(['password_hash'=>$hash,'id'=>$userId]);
jsonResponse(200,['ok'=>true]);
