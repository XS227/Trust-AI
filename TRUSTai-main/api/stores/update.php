<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';
requireRole('super_admin');
$body=readJsonBody();$storeId=(int)($body['id']??0);if($storeId<=0){jsonResponse(400,['ok'=>false]);}
$fields=['name','platform','domain','public_url','default_commission_percent','status','contact_name','contact_email','contact_phone','contact_title','owner_user_id'];$set=[];$params=['id'=>$storeId];
foreach($fields as $f){if(!array_key_exists($f,$body)) continue; $v=$body[$f]; if($f==='domain'){$v=strtolower(preg_replace('#/$#','',preg_replace('#^https?://#','',trim((string)$v))));} $set[]="$f=:$f"; $params[$f]=$v;}
if(isset($params['default_commission_percent']) && ((float)$params['default_commission_percent']<0 || (float)$params['default_commission_percent']>100)){jsonResponse(400,['ok'=>false,'error'=>'validation_failed']);}
if(isset($params['public_url']) && $params['public_url']!=='' && !filter_var((string)$params['public_url'], FILTER_VALIDATE_URL)){jsonResponse(400,['ok'=>false,'error'=>'validation_failed']);}
if(!$set){jsonResponse(400,['ok'=>false,'error'=>'no_fields']);}
$pdo->prepare('UPDATE stores SET '.implode(',',$set).' WHERE id=:id')->execute($params);
if(array_key_exists('owner_user_id',$body)&&(int)$body['owner_user_id']>0){$pdo->prepare('UPDATE users SET role=:role, store_id=:store_id WHERE id=:id')->execute(['role'=>'store_admin','store_id'=>$storeId,'id'=>(int)$body['owner_user_id']]);}
jsonResponse(200,['ok'=>true,'data'=>['store_id'=>$storeId]]);
