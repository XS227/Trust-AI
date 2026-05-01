<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';
requireRole('super_admin');
$body = readJsonBody();
$name=trim((string)($body['name']??''));
$platform=trim((string)($body['platform']??'Shopify'));
$domain=strtolower(preg_replace('#/$#','',preg_replace('#^https?://#','',trim((string)($body['domain']??'')))));
$publicUrl=trim((string)($body['public_url']??''));
$commission=(float)($body['default_commission_percent']??0);
$status=trim((string)($body['status']??'active'));
if($name===''||$domain===''||$commission<0||$commission>100||!filter_var($publicUrl,FILTER_VALIDATE_URL)){jsonResponse(400,['ok'=>false,'error'=>'validation_failed']);}
$pdo->beginTransaction();
try{
$stmt=$pdo->prepare('INSERT INTO stores (name,platform,domain,public_url,default_commission_percent,status,contact_name,contact_email,contact_phone,contact_title,created_at) VALUES (:name,:platform,:domain,:public_url,:default_commission_percent,:status,:contact_name,:contact_email,:contact_phone,:contact_title,NOW())');
$stmt->execute(['name'=>$name,'platform'=>$platform,'domain'=>$domain,'public_url'=>$publicUrl!==''?$publicUrl:null,'default_commission_percent'=>$commission,'status'=>$status,'contact_name'=>trim((string)($body['contact_name']??''))?:null,'contact_email'=>strtolower(trim((string)($body['contact_email']??'')))?:null,'contact_phone'=>trim((string)($body['contact_phone']??''))?:null,'contact_title'=>trim((string)($body['contact_title']??''))?:null]);
$storeId=(int)$pdo->lastInsertId();$adminOut=null;
if(!empty($body['create_store_admin'])){
$email=strtolower(trim((string)($body['contact_email']??'')));$pass=(string)($body['temporary_password']??'');$contact=trim((string)($body['contact_name']??''));
if($email===''||$pass===''){throw new RuntimeException('admin_fields_required');}
$ust=$pdo->prepare('INSERT INTO users (email,password_hash,role,store_id,must_change_password,created_at) VALUES (:email,:password_hash,:role,:store_id,:must_change_password,NOW())');
$ust->execute(['email'=>$email,'password_hash'=>password_hash($pass,PASSWORD_DEFAULT),'role'=>'store_admin','store_id'=>$storeId,'must_change_password'=>!empty($body['must_change_password'])?1:0]);
$uid=(int)$pdo->lastInsertId();
$pdo->prepare('UPDATE stores SET owner_user_id=:uid WHERE id=:id')->execute(['uid'=>$uid,'id'=>$storeId]);
$adminOut=['id'=>$uid,'email'=>$email,'name'=>$contact,'must_change_password'=>!empty($body['must_change_password'])?1:0];
}
$pdo->commit();
jsonResponse(201,['ok'=>true,'data'=>['store_id'=>$storeId,'store_admin'=>$adminOut]]);
}catch(Throwable $e){$pdo->rollBack();jsonResponse(400,['ok'=>false,'error'=>$e->getMessage()]);}
