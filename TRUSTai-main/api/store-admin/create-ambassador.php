<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';
$user=requireLogin();
if(!in_array($user['role'],['store_admin','super_admin'],true)) jsonResponse(403,['ok'=>false]);
$b=readJsonBody();
$storeId=(int)($b['store_id']??$user['store_id']??0); if(($user['role']==='store_admin') && $storeId!==(int)$user['store_id']) jsonResponse(403,['ok'=>false]);
$name=trim((string)($b['name']??''));$email=strtolower(trim((string)($b['email']??''))); 
$commission=(float)($b['commission_percent']??0);$status=trim((string)($b['status']??'approved'));$pass=(string)($b['temporary_password']??'');
if($name===''||$email===''||$pass===''||$commission<0||$commission>100) jsonResponse(400,['ok'=>false,'error'=>'validation']);
$ref=trim((string)($b['referral_code']??'')); if($ref==='') $ref=strtolower(substr(preg_replace('/[^a-z0-9]/i','',$name),0,6)).substr(bin2hex(random_bytes(3)),0,6);
$pdo->beginTransaction();
try{
$pdo->prepare('INSERT INTO users (email,password_hash,role,store_id,must_change_password,created_at) VALUES (:email,:ph,:role,:store_id,:must_change_password,NOW())')->execute(['email'=>$email,'ph'=>password_hash($pass,PASSWORD_DEFAULT),'role'=>'ambassador','store_id'=>$storeId,'must_change_password'=>!empty($b['must_change_password'])?1:0]);
$uid=(int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO ambassadors (store_id,user_id,name,email,phone,payout_account,referral_code,status,commission_percent,created_at,approved_at) VALUES (:store_id,:user_id,:name,:email,:phone,:payout_account,:referral_code,:status,:commission_percent,NOW(),IF(:status="approved",NOW(),NULL))')->execute(['store_id'=>$storeId,'user_id'=>$uid,'name'=>$name,'email'=>$email,'phone'=>trim((string)($b['phone']??''))?:null,'payout_account'=>trim((string)($b['payout_account']??''))?:null,'referral_code'=>$ref,'status'=>$status,'commission_percent'=>$commission]);
$aid=(int)$pdo->lastInsertId();$pdo->prepare('UPDATE users SET ambassador_id=:aid WHERE id=:uid')->execute(['aid'=>$aid,'uid'=>$uid]);
$pdo->commit();
jsonResponse(201,['ok'=>true,'ambassador'=>['id'=>$aid,'store_id'=>$storeId,'user_id'=>$uid,'name'=>$name,'email'=>$email,'referral_code'=>$ref,'status'=>$status,'commission_percent'=>$commission]]);
}catch(Throwable $e){$pdo->rollBack();jsonResponse(400,['ok'=>false,'error'=>$e->getMessage()]);}
