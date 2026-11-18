<?php
declare(strict_types=1);
/**
 * Authenticated, same-origin download of partner invoice PDF.
 * No direct URL ever exposes the private storage directory.
 */
require_once __DIR__ . '/../_auth.php';
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header_remove('Access-Control-Allow-Origin');
header_remove('Access-Control-Allow-Credentials');
if (($_SERVER['REQUEST_METHOD'] ?? '')!=='GET') jsonResponse(405,['ok'=>false,'error'=>'method_not_allowed']);
if (($_SERVER['HTTP_ORIGIN'] ?? '')!=='' && $_SERVER['HTTP_ORIGIN']!=='https://trustai.no') jsonResponse(403,['ok'=>false,'error'=>'origin_denied']);
if (!$pdo instanceof PDO) jsonResponse(503,['ok'=>false,'error'=>'database_unavailable']);
$uid=(int)($_SESSION['trustai_user_id'] ?? $_SESSION['user_id'] ?? 0);
if ($uid<=0) jsonResponse(401,['ok'=>false,'error'=>'login_required']);
$s=$pdo->prepare('SELECT role,vipps_sub,status FROM users WHERE id=?');
$s->execute([$uid]);
$user=$s->fetch(PDO::FETCH_ASSOC);
if (!$user || $user['status']!=='active') jsonResponse(401,['ok'=>false,'error'=>'login_required']);
$id=(int)($_GET['id'] ?? 0);
if ($id<=0) jsonResponse(422,['ok'=>false,'error'=>'invalid_invoice_id']);
$isAdmin=$user['role']==='super_admin';
if (!$isAdmin && ($user['role']!=='ambassador' || empty($user['vipps_sub']))) {
    jsonResponse(403,['ok'=>false,'error'=>'partner_or_admin_only']);
}
$s=$pdo->prepare('SELECT i.id,i.storage_filename,i.sha256,p.user_id FROM b2b_partner_invoices i JOIN b2b_partners p ON p.id=i.partner_id WHERE i.id=? LIMIT 1');
$s->execute([$id]);
$invoice=$s->fetch(PDO::FETCH_ASSOC);
if (!$invoice || (!$isAdmin && (int)$invoice['user_id']!==$uid)) {
    jsonResponse(404,['ok'=>false,'error'=>'invoice_not_found']);
}
$dirConfig=trim((string)(getenv('TRUSTAI_PRIVATE_INVOICE_DIR') ?: ''));
$dir=$dirConfig!=='' ? realpath($dirConfig) : false;
$docRoot=realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? ''));
if (!$dir || !$docRoot || $dir===$docRoot || strpos($dir,$docRoot.DIRECTORY_SEPARATOR)===0) {
    jsonResponse(503,['ok'=>false,'error'=>'private_invoice_storage_not_configured']);
}
$storage=(string)$invoice['storage_filename'];
if (!preg_match('/^[a-f0-9]{32}\.pdf$/D',$storage)) jsonResponse(500,['ok'=>false,'error'=>'invalid_storage_reference']);
$path=realpath($dir.DIRECTORY_SEPARATOR.$storage);
if (!$path || dirname($path)!==$dir || !is_file($path) || !is_readable($path)) {
    jsonResponse(404,['ok'=>false,'error'=>'invoice_file_not_found']);
}
if (!hash_equals((string)$invoice['sha256'],(string)hash_file('sha256',$path))) {
    error_log('TrustAI private partner invoice SHA mismatch id='.$id);
    jsonResponse(500,['ok'=>false,'error'=>'invoice_integrity_failure']);
}
$size=filesize($path);
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="trustai-partner-invoice-'.$id.'.pdf"');
header('Content-Length: '.(int)$size);
readfile($path);
exit;
