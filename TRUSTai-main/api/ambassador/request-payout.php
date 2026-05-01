<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';
$user=requireRole('ambassador');$ambassadorId=(int)$user['ambassador_id'];$storeId=(int)$user['store_id'];requireAmbassadorAccess($ambassadorId);
$amount=(float)($_POST['amount']??0);$comment=trim((string)($_POST['comment']??'')); if($amount<=0) jsonResponse(400,['ok'=>false,'error'=>'invalid_amount']);
$invoicePath=null;
if(!empty($_FILES['invoice_file']) && is_uploaded_file($_FILES['invoice_file']['tmp_name'])){
$f=$_FILES['invoice_file']; if(($f['size']??0)>10*1024*1024) jsonResponse(400,['ok'=>false,'error'=>'file_too_large']);
$ext=strtolower(pathinfo((string)$f['name'],PATHINFO_EXTENSION)); if(!in_array($ext,['pdf','jpg','jpeg','png'],true)) jsonResponse(400,['ok'=>false,'error'=>'file_type']);
$dir=__DIR__.'/../../uploads/invoices/'.$storeId.'/'.$ambassadorId; if(!is_dir($dir)) mkdir($dir,0755,true);
$filename='invoice_'.date('Ymd_His').'_'.$ambassadorId.'.'.$ext; $target=$dir.'/'.$filename; move_uploaded_file($f['tmp_name'],$target);
$invoicePath='/uploads/invoices/'.$storeId.'/'.$ambassadorId.'/'.$filename;
}
$pdo->prepare('INSERT INTO payouts (store_id,ambassador_id,amount,status,invoice_url,invoice_file_path,comment,created_at) VALUES (:store_id,:ambassador_id,:amount,:status,:invoice_url,:invoice_file_path,:comment,NOW())')->execute(['store_id'=>$storeId,'ambassador_id'=>$ambassadorId,'amount'=>$amount,'status'=>'requested','invoice_url'=>$invoicePath,'invoice_file_path'=>$invoicePath,'comment'=>$comment!==''?$comment:null]);
jsonResponse(201,['ok'=>true,'payout_id'=>(int)$pdo->lastInsertId(),'invoice_url'=>$invoicePath]);
