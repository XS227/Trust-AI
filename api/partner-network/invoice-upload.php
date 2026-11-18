<?php
declare(strict_types=1);
/**
 * Private partner PDF invoice submission.
 * Bytes are never stored in the Git repository or served via static URLs.
 * Requires TRUSTAI_PRIVATE_INVOICE_DIR outside the public document root.
 */
require_once __DIR__ . '/../_auth.php';
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header_remove('Access-Control-Allow-Origin');
header_remove('Access-Control-Allow-Credentials');
if (($_SERVER['REQUEST_METHOD'] ?? '')!=='POST') jsonResponse(405,['ok'=>false,'error'=>'method_not_allowed']);
if (($_SERVER['HTTP_ORIGIN'] ?? '')!=='' && $_SERVER['HTTP_ORIGIN']!=='https://trustai.no') jsonResponse(403,['ok'=>false,'error'=>'origin_denied']);
if (!($pdo instanceof PDO)) jsonResponse(503,['ok'=>false,'error'=>'database_unavailable']);
if (empty($_SESSION['b2b_csrf']) || !hash_equals((string)$_SESSION['b2b_csrf'],(string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
    jsonResponse(403,['ok'=>false,'error'=>'invalid_csrf']);
}
$uid=(int)($_SESSION['trustai_user_id'] ?? $_SESSION['user_id'] ?? 0);
if ($uid<=0) jsonResponse(401,['ok'=>false,'error'=>'vipps_login_required']);
$s=$pdo->prepare('SELECT role,vipps_sub,status FROM users WHERE id=? LIMIT 1');
$s->execute([$uid]);
$u=$s->fetch(PDO::FETCH_ASSOC);
if (!$u || $u['role']!=='ambassador' || empty($u['vipps_sub']) || $u['status']!=='active') jsonResponse(403,['ok'=>false,'error'=>'vipps_partner_required']);
$programId=(int)($_POST['program_id'] ?? 0);
$referralId=(int)($_POST['referral_id'] ?? 0);
$invoiceNumber=mb_substr(trim((string)($_POST['invoice_number'] ?? '')),0,100);
$invoiceId=(int)($_POST['invoice_id'] ?? 0);
$amountRaw=(string)($_POST['claimed_amount'] ?? '');
if ($programId<=0 || $referralId<=0 || !preg_match('/^[\p{L}\p{N}][\p{L}\p{N} ._\/-]{1,99}$/u',$invoiceNumber)
    || !preg_match('/^\d{1,9}(?:\.\d{1,2})?$/D',$amountRaw) || (float)$amountRaw<=0) {
    jsonResponse(422,['ok'=>false,'error'=>'invalid_invoice_data']);
}
$amount=round((float)$amountRaw,2);
$s=$pdo->prepare('SELECT p.id AS partner_id FROM b2b_partners p JOIN b2b_programs g ON g.id=p.program_id JOIN b2b_referrals r ON r.partner_id=p.id AND r.program_id=p.program_id WHERE p.program_id=? AND p.user_id=? AND p.status="active" AND g.status="active" AND r.id=? AND r.approval_status="approved" LIMIT 1');
$s->execute([$programId,$uid,$referralId]);
$partnerId=(int)$s->fetchColumn();
if (!$partnerId) jsonResponse(403,['ok'=>false,'error'=>'approved_referral_required']);
$file=$_FILES['invoice_pdf'] ?? null;
if (!is_array($file) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK
    || (int)($file['size'] ?? 0)<100 || (int)($file['size'] ?? 0)>8*1024*1024
    || !is_uploaded_file((string)($file['tmp_name'] ?? ''))) {
    jsonResponse(422,['ok'=>false,'error'=>'valid_pdf_under_8mb_required']);
}
$tmp=(string)$file['tmp_name'];
$head=file_get_contents($tmp,false,null,0,5);
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
if ($head!=='%PDF-' || !in_array($mime,['application/pdf','application/x-pdf'],true)) {
    jsonResponse(422,['ok'=>false,'error'=>'pdf_format_required']);
}
$directoryConfig=trim((string)(getenv('TRUSTAI_PRIVATE_INVOICE_DIR') ?: ''));
$dir=$directoryConfig!=='' ? realpath($directoryConfig) : false;
$docRoot=realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? ''));
if (!$dir || !is_dir($dir) || !is_writable($dir) || !$docRoot
    || $dir===$docRoot || strpos($dir,$docRoot.DIRECTORY_SEPARATOR)===0) {
    error_log('TrustAI partner invoice private directory is not configured safely');
    jsonResponse(503,['ok'=>false,'error'=>'private_invoice_storage_not_configured']);
}
$storage=bin2hex(random_bytes(16)).'.pdf';
$path=$dir.DIRECTORY_SEPARATOR.$storage;
if (file_exists($path) || !move_uploaded_file($tmp,$path)) {
    jsonResponse(503,['ok'=>false,'error'=>'invoice_storage_failure']);
}
@chmod($path,0600);
$original=mb_substr(basename(str_replace('\\','/',(string)($file['name'] ?? 'faktura.pdf'))),0,180);
$digest=hash_file('sha256',$path);
if (!$digest) { @unlink($path); jsonResponse(503,['ok'=>false,'error'=>'file_integrity_error']); }
$oldStorage=null;
try {
    $pdo->beginTransaction();
    if ($invoiceId>0) {
        $s=$pdo->prepare('SELECT * FROM b2b_partner_invoices WHERE id=? AND program_id=? AND partner_id=? FOR UPDATE');
        $s->execute([$invoiceId,$programId,$partnerId]);
        $old=$s->fetch(PDO::FETCH_ASSOC);
        if (!$old || $old['status']!=='needs_revision'
            || (int)$old['referral_id']!==$referralId || $old['invoice_number']!==$invoiceNumber) {
            $pdo->rollBack(); @unlink($path); jsonResponse(409,['ok'=>false,'error'=>'invoice_revision_not_allowed']);
        }
        $oldStorage=(string)$old['storage_filename'];
        $s=$pdo->prepare('UPDATE b2b_partner_invoices SET claimed_amount=?,original_filename=?,storage_filename=?,file_size_bytes=?,sha256=?,status="submitted",admin_note=NULL,reviewed_by_user_id=NULL,reviewed_at=NULL WHERE id=?');
        $s->execute([$amount,$original,$storage,(int)$file['size'],$digest,$invoiceId]);
    } else {
        $s=$pdo->prepare('INSERT INTO b2b_partner_invoices (program_id,partner_id,referral_id,invoice_number,claimed_amount,original_filename,storage_filename,file_size_bytes,sha256) VALUES (?,?,?,?,?,?,?,?,?)');
        $s->execute([$programId,$partnerId,$referralId,$invoiceNumber,$amount,$original,$storage,(int)$file['size'],$digest]);
        $invoiceId=(int)$pdo->lastInsertId();
    }
    $s=$pdo->prepare('INSERT INTO b2b_audit (actor_user_id,event_type,object_type,object_id) VALUES (?,?,?,?)');
    $s->execute([$uid,$oldStorage?'resubmit_invoice':'upload_invoice','partner_invoice',$invoiceId]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    @unlink($path);
    error_log('TrustAI B2B invoice write: '.$e->getMessage());
    if ($e instanceof PDOException && ($e->errorInfo[1] ?? 0)===1062) {
        jsonResponse(409,['ok'=>false,'error'=>'invoice_number_already_used']);
    }
    jsonResponse(500,['ok'=>false,'error'=>'invoice_submission_failed']);
}
if ($oldStorage && preg_match('/^[a-f0-9]{32}\.pdf$/D',$oldStorage)) {
    @unlink($dir.DIRECTORY_SEPARATOR.$oldStorage);
}
jsonResponse(201,['ok'=>true,'invoice_id'=>$invoiceId,'status'=>'submitted','message'=>'Faktura mottatt for kontroll. Ingen automatisk utbetaling.']);
