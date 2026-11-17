<?php
declare(strict_types=1);
/**
 * TrustAI B2B partner network. Independent from ecommerce/Shopify commission logic.
 * Auth is derived from a verified server-side session and DB role, never HTTP_X_USER_ID.
 */
require_once __DIR__ . '/../_auth.php';
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
// Legacy _auth.php reflects arbitrary CORS origins; financial B2B data must not.
header_remove('Access-Control-Allow-Origin');
header_remove('Access-Control-Allow-Credentials');
$incomingOrigin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
if ($incomingOrigin !== '' && $incomingOrigin !== 'https://trustai.no') {
    jsonResponse(403, ['ok' => false, 'error' => 'origin_denied']);
}

if (!$pdo instanceof PDO) {
    jsonResponse(503, ['ok' => false, 'error' => 'database_unavailable']);
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function b2bFail(int $status, string $error): void {
    jsonResponse($status, ['ok' => false, 'error' => $error]);
}
function b2bAudit(PDO $db, int $actor, string $event, string $type, int $id): void {
    $s = $db->prepare('INSERT INTO b2b_audit (actor_user_id,event_type,object_type,object_id) VALUES (?,?,?,?)');
    $s->execute([$actor,$event,$type,$id]);
}
function b2bMoney($value): float {
    if (!is_numeric($value)) b2bFail(422,'invalid_amount');
    $number = (float)$value;
    if (!is_finite($number) || $number < 0 || $number > 9999999999) b2bFail(422,'invalid_amount');
    return round($number,2);
}
function b2bString($v, int $limit = 160): string {
    if (!is_string($v)) return '';
    return mb_substr(trim($v),0,$limit);
}
function b2bOrgValid(string $org): bool {
    if (!preg_match('/^\d{9}$/D',$org)) return false;
    $weights = [3,2,7,6,5,4,3,2];
    $sum = 0;
    for ($i=0;$i<8;$i++) $sum += (int)$org[$i] * $weights[$i];
    $check = 11 - ($sum % 11);
    if ($check === 11) $check = 0;
    return $check !== 10 && $check === (int)$org[8];
}
/**
 * Exact, public Norwegian company-register lookup (fixed host + numeric identifier).
 * Avoids the need to trust a partner-supplied company name.
 */
function b2bRegistryName(string $org): ?string {
    if (!b2bOrgValid($org)) return null;
    $ch = curl_init('https://data.brreg.no/enhetsregisteret/api/enheter/' . $org);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_USERAGENT => 'TrustAI-B2B/1.0 (trustai.no)'
    ]);
    $raw = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http !== 200 || !is_string($raw)) return null;
    $data = json_decode($raw,true);
    return is_array($data) && !empty($data['navn']) && is_string($data['navn'])
        ? b2bString($data['navn'],200) : null;
}
function b2bProgram(PDO $db, int $id): array {
    $s=$db->prepare('SELECT * FROM b2b_programs WHERE id=? AND status="active"');
    $s->execute([$id]);
    $r=$s->fetch(PDO::FETCH_ASSOC);
    if (!$r) b2bFail(404,'program_not_found');
    return $r;
}
function b2bMember(PDO $db, int $programId, int $userId): array {
    $s=$db->prepare('SELECT * FROM b2b_partners WHERE program_id=? AND user_id=? AND status="active"');
    $s->execute([$programId,$userId]);
    $r=$s->fetch(PDO::FETCH_ASSOC);
    if (!$r) b2bFail(403,'not_partner_in_program');
    return $r;
}
function b2bCommissionRows(PDO $db, array $sale, array $referral, array $program): array {
    $margin = max(0.0,round((float)$sale['revenue_ex_vat'] - (float)$sale['direct_cost_ex_vat'],2));
    $mode = (string)$sale['commission_mode'];
    $pool = $mode === 'fixed'
        ? b2bMoney($sale['fixed_commission'] ?? 0)
        : round($margin * (float)$program['default_commission_percent'] / 100,2);
    if ($pool > $margin) b2bFail(422,'commission_exceeds_positive_profit');
    $s=$db->prepare('SELECT id,parent_partner_id FROM b2b_partners WHERE id=? AND program_id=? AND status="active"');
    $s->execute([(int)$referral['partner_id'],(int)$sale['program_id']]);
    $direct=$s->fetch(PDO::FETCH_ASSOC);
    if (!$direct) b2bFail(422,'source_partner_inactive');

    $upline=0.0;
    $parent=null;
    if ((int)$direct['parent_partner_id']>0) {
        $s=$db->prepare('SELECT id,upline_share_percent FROM b2b_partners WHERE id=? AND program_id=? AND status="active"');
        $s->execute([(int)$direct['parent_partner_id'],(int)$sale['program_id']]);
        $parent=$s->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($parent) {
            $ratio=min(30.0,max(0.0,(float)$parent['upline_share_percent']));
            $upline=round($pool*$ratio/100,2);
        }
    }
    $rows=[['partner_id'=>(int)$direct['id'],'kind'=>'direct','amount'=>round($pool-$upline,2)]];
    if ($parent && $upline>0) {
        $rows[]=['partner_id'=>(int)$parent['id'],'kind'=>'upline','amount'=>$upline];
    }
    return $rows;
}

// Strict server-side authenticated session, not the legacy X-User-Id header fallback.
$sessionUserId=(int)($_SESSION['trustai_user_id'] ?? $_SESSION['user_id'] ?? 0);
if ($sessionUserId<=0) b2bFail(401,'login_required');
$s=$pdo->prepare('SELECT id,email,role,status,vipps_sub FROM users WHERE id=? LIMIT 1');
$s->execute([$sessionUserId]);
$actor=$s->fetch(PDO::FETCH_ASSOC);
if (!$actor || in_array(strtolower((string)$actor['status']),['blocked','suspended','inactive'],true)) {
    b2bFail(401,'login_required');
}
$admin=$actor['role']==='super_admin';
$vipps=(string)($actor['vipps_sub'] ?? '')!=='';
if (!$admin && (!$vipps || $actor['role']!=='ambassador')) b2bFail(403,'vipps_ambassador_required');
$uid=(int)$actor['id'];
$action=(string)($_GET['action'] ?? 'dashboard');
$method=(string)($_SERVER['REQUEST_METHOD'] ?? 'GET');
if (empty($_SESSION['b2b_csrf'])) $_SESSION['b2b_csrf']=bin2hex(random_bytes(24));

if ($method==='GET') {
    if ($action==='company_lookup') {
        $org=(string)($_GET['org_number'] ?? '');
        if (!b2bOrgValid($org)) b2bFail(422,'invalid_norwegian_org_number');
        $name=b2bRegistryName($org);
        if (!$name) b2bFail(404,'company_not_found_or_registry_unavailable');
        jsonResponse(200,['ok'=>true,'org_number'=>$org,'name'=>$name]);
    }
    if ($action!=='dashboard') b2bFail(404,'unknown_action');
    $programId=(int)($_GET['program_id'] ?? 0);
    if (!$admin) {
        if ($programId<=0) {
            $s=$pdo->prepare('SELECT program_id FROM b2b_partners WHERE user_id=? AND status="active" ORDER BY id LIMIT 1');
            $s->execute([$uid]);
            $programId=(int)$s->fetchColumn();
        }
    } elseif ($programId<=0) {
        $programId=(int)$pdo->query('SELECT id FROM b2b_programs WHERE status="active" ORDER BY id LIMIT 1')->fetchColumn();
    }
    $base=['ok'=>true,'is_admin'=>$admin,'csrf'=>$_SESSION['b2b_csrf'],'joined'=>false,'program'=>null,'partners'=>[],'referrals'=>[],'sales'=>[],'commissions'=>[],'invites'=>[]];
    if (!$programId) jsonResponse(200,$base);
    $program=b2bProgram($pdo,$programId);
    $base['program']=['id'=>(int)$program['id'],'name'=>$program['name'],'currency'=>$program['currency'],
        'default_commission_percent'=>(float)$program['default_commission_percent']];
    $member=null;
    if (!$admin) {
        $member=b2bMember($pdo,$programId,$uid);
        $base['joined']=true;
        $base['membership']=['id'=>(int)$member['id'],'display_name'=>$member['display_name'],
            'type'=>$member['type'],'parent_partner_id'=>$member['parent_partner_id']];
    } else $base['joined']=true;

    if ($admin) {
        $s=$pdo->prepare('SELECT id,user_id,parent_partner_id,display_name,type,status,upline_share_percent FROM b2b_partners WHERE program_id=? ORDER BY id');
        $s->execute([$programId]);
        $base['partners']=$s->fetchAll(PDO::FETCH_ASSOC);
        $s=$pdo->prepare('SELECT r.id,r.partner_id,r.org_number,r.company_name,r.brand_name,r.status,r.partner_note,r.created_at,p.display_name AS partner_name FROM b2b_referrals r JOIN b2b_partners p ON p.id=r.partner_id WHERE r.program_id=? ORDER BY r.created_at DESC LIMIT 300');
        $s->execute([$programId]);
        $base['referrals']=$s->fetchAll(PDO::FETCH_ASSOC);
        $s=$pdo->prepare('SELECT s.*,r.company_name,r.brand_name, GREATEST(0,s.revenue_ex_vat-s.direct_cost_ex_vat) AS profit FROM b2b_sales s JOIN b2b_referrals r ON r.id=s.referral_id WHERE s.program_id=? ORDER BY s.id DESC LIMIT 300');
        $s->execute([$programId]);
        $base['sales']=$s->fetchAll(PDO::FETCH_ASSOC);
        $s=$pdo->prepare('SELECT c.id,c.sale_id,c.partner_id,c.kind,c.amount,c.status,c.paid_at,r.company_name,r.brand_name,p.display_name AS partner_name FROM b2b_commissions c JOIN b2b_sales s ON s.id=c.sale_id JOIN b2b_referrals r ON r.id=s.referral_id JOIN b2b_partners p ON p.id=c.partner_id WHERE s.program_id=? ORDER BY c.id DESC LIMIT 400');
        $s->execute([$programId]);
        $base['commissions']=$s->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $s=$pdo->prepare('SELECT id,partner_id,org_number,company_name,brand_name,status,created_at FROM b2b_referrals WHERE program_id=? AND partner_id=? ORDER BY id DESC LIMIT 200');
        $s->execute([$programId,(int)$member['id']]);
        $base['referrals']=$s->fetchAll(PDO::FETCH_ASSOC);
        $s=$pdo->prepare('SELECT c.id,c.kind,c.amount,c.status,c.paid_at,r.company_name,r.brand_name,s.invoice_reference FROM b2b_commissions c JOIN b2b_sales s ON s.id=c.sale_id JOIN b2b_referrals r ON r.id=s.referral_id WHERE c.partner_id=? AND s.program_id=? ORDER BY c.id DESC LIMIT 200');
        $s->execute([(int)$member['id'],$programId]);
        $base['commissions']=$s->fetchAll(PDO::FETCH_ASSOC);
        $s=$pdo->prepare('SELECT id,display_name,type,status,created_at FROM b2b_partners WHERE parent_partner_id=? AND program_id=? ORDER BY id DESC');
        $s->execute([(int)$member['id'],$programId]);
        $base['partners']=$s->fetchAll(PDO::FETCH_ASSOC);
    }
    jsonResponse(200,$base);
}

if ($method!=='POST') b2bFail(405,'method_not_allowed');
// Same-origin checks are applied above for both reads and writes.
$body=readJsonBody();
if (!hash_equals((string)$_SESSION['b2b_csrf'],(string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
    b2bFail(403,'invalid_csrf');
}
$programId=(int)($body['program_id'] ?? 0);
try {
    if ($action==='create_program') {
        if (!$admin) b2bFail(403,'admin_only');
        $name=b2bString($body['name'] ?? '',160);
        if (mb_strlen($name)<2) b2bFail(422,'name_required');
        $s=$pdo->prepare('INSERT INTO b2b_programs (name) VALUES (?)');
        $s->execute([$name]);
        $id=(int)$pdo->lastInsertId();
        b2bAudit($pdo,$uid,'create','program',$id);
        jsonResponse(201,['ok'=>true,'id'=>$id]);
    }
    if ($action==='accept_invite') {
        if (!$vipps || $actor['role']!=='ambassador') b2bFail(403,'vipps_ambassador_required');
        $token=b2bString($body['token'] ?? '',180);
        if (strlen($token)<30) b2bFail(422,'invalid_invite');
        $pdo->beginTransaction();
        $s=$pdo->prepare('SELECT * FROM b2b_invites WHERE token_hash=? AND claimed_at IS NULL AND expires_at>NOW() FOR UPDATE');
        $s->execute([hash('sha256',$token)]);
        $invite=$s->fetch(PDO::FETCH_ASSOC);
        if (!$invite) { $pdo->rollBack(); b2bFail(410,'invite_expired_or_used'); }
        $pid=(int)$invite['program_id'];
        b2bProgram($pdo,$pid);
        $s=$pdo->prepare('SELECT id FROM b2b_partners WHERE user_id=? AND program_id=?');
        $s->execute([$uid,$pid]);
        if ($s->fetchColumn()) { $pdo->rollBack(); b2bFail(409,'already_member'); }
        if ($invite['parent_partner_id']!==null) {
            $s=$pdo->prepare('SELECT id FROM b2b_partners WHERE id=? AND program_id=? AND status="active"');
            $s->execute([(int)$invite['parent_partner_id'],$pid]);
            if (!$s->fetchColumn()) { $pdo->rollBack(); b2bFail(409,'inviter_inactive'); }
        }
        $s=$pdo->prepare('INSERT INTO b2b_partners (program_id,user_id,parent_partner_id,display_name,type) VALUES (?,?,?,?,?)');
        $s->execute([$pid,$uid,$invite['parent_partner_id'],(string)$invite['invited_label'],(string)$invite['member_type']]);
        $id=(int)$pdo->lastInsertId();
        $pdo->prepare('UPDATE b2b_invites SET claimed_at=NOW(),claimed_user_id=? WHERE id=?')->execute([$uid,(int)$invite['id']]);
        b2bAudit($pdo,$uid,'join','partner',$id);
        $pdo->commit();
        jsonResponse(201,['ok'=>true,'id'=>$id,'program_id'=>$pid]);
    }
    $program=b2bProgram($pdo,$programId);
    $member=$admin ? null : b2bMember($pdo,$programId,$uid);

    if ($action==='set_upline_share') {
        if (!$admin) b2bFail(403,'admin_only');
        $partnerId=(int)($body['partner_id'] ?? 0);
        $pct=$body['upline_share_percent'] ?? null;
        if ($partnerId<=0 || !is_numeric($pct) || !is_finite((float)$pct) || (float)$pct<0 || (float)$pct>30) b2bFail(422,'invalid_upline_percent');
        $s=$pdo->prepare('UPDATE b2b_partners SET upline_share_percent=? WHERE id=? AND program_id=?');
        $s->execute([round((float)$pct,2),$partnerId,$programId]);
        if (!$s->rowCount()) b2bFail(404,'partner_not_found_or_unchanged');
        b2bAudit($pdo,$uid,'upline_percent','partner',$partnerId);
        jsonResponse(200,['ok'=>true]);
    }
    if ($action==='create_invite') {
        $parentId=$admin ? (int)($body['parent_partner_id'] ?? 0) : (int)$member['id'];
        if ($parentId) {
            $s=$pdo->prepare('SELECT id FROM b2b_partners WHERE id=? AND program_id=? AND status="active"');
            $s->execute([$parentId,$programId]);
            if (!$s->fetchColumn()) b2bFail(422,'invalid_parent');
        }
        $label=b2bString($body['label'] ?? '',160);
        $partnerType=(string)($body['partner_type'] ?? 'partner');
        if (!in_array($partnerType,['partner','seller','ambassador'],true)) b2bFail(422,'invalid_partner_type');
        if (mb_strlen($label)<2) b2bFail(422,'label_required');
        $token=rtrim(strtr(base64_encode(random_bytes(32)),'+/','-_'),'=');
        $s=$pdo->prepare('INSERT INTO b2b_invites (program_id,parent_partner_id,invited_label,member_type,token_hash,created_by_user_id,expires_at) VALUES (?,?,?,?,?,?,DATE_ADD(NOW(),INTERVAL 14 DAY))');
        $s->execute([$programId,$parentId ?: null,$label,$partnerType,hash('sha256',$token),$uid]);
        $id=(int)$pdo->lastInsertId();
        b2bAudit($pdo,$uid,'invite','invite',$id);
        jsonResponse(201,['ok'=>true,'invite_url'=>'https://trustai.no/partner-network.html?invite='.$token,'expires_in_days'=>14]);
    }
    if ($action==='create_referral') {
        $partnerId=$admin ? (int)($body['partner_id'] ?? 0) : (int)$member['id'];
        $s=$pdo->prepare('SELECT id FROM b2b_partners WHERE id=? AND program_id=? AND status="active"');
        $s->execute([$partnerId,$programId]);
        if (!$s->fetchColumn()) b2bFail(422,'invalid_partner');
        $org=preg_replace('/\s+/','',(string)($body['org_number'] ?? ''));
        $company=b2bString($body['company_name'] ?? '',200);
        $brand=b2bString($body['brand_name'] ?? '',160);
        $note=b2bString($body['note'] ?? '',1000);
        if (!b2bOrgValid($org)) b2bFail(422,'invalid_norwegian_org_number');
        // Prefer the verified registered company name whenever the registry responds.
        $registeredName=b2bRegistryName($org);
        if ($registeredName!==null) $company=$registeredName;
        if (mb_strlen($company)<2) b2bFail(422,'company_name_required');
        try {
            $s=$pdo->prepare('INSERT INTO b2b_referrals (program_id,partner_id,org_number,company_name,brand_name,partner_note) VALUES (?,?,?,?,?,?)');
            $s->execute([$programId,$partnerId,$org,$company,$brand ?: null,$note ?: null]);
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null)==1062) b2bFail(409,'company_already_referred');
            throw $e;
        }
        $id=(int)$pdo->lastInsertId();
        b2bAudit($pdo,$uid,'register','referral',$id);
        jsonResponse(201,['ok'=>true,'id'=>$id]);
    }
    if ($action==='set_referral_status') {
        if (!$admin) b2bFail(403,'admin_only');
        $id=(int)($body['referral_id'] ?? 0);
        $status=(string)($body['status'] ?? '');
        if (!in_array($status,['new','contacted','meeting','offer','won','lost'],true)) b2bFail(422,'invalid_status');
        $s=$pdo->prepare('UPDATE b2b_referrals SET status=? WHERE id=? AND program_id=?');
        $s->execute([$status,$id,$programId]);
        if (!$s->rowCount()) b2bFail(404,'referral_not_found_or_unchanged');
        b2bAudit($pdo,$uid,'status','referral',$id);
        jsonResponse(200,['ok'=>true]);
    }
    if ($action==='save_sale') {
        if (!$admin) b2bFail(403,'admin_only');
        $saleId=(int)($body['sale_id'] ?? 0);
        $refId=(int)($body['referral_id'] ?? 0);
        $invoice=b2bString($body['invoice_reference'] ?? '',120);
        $revenue=b2bMoney($body['revenue_ex_vat'] ?? null);
        $cost=b2bMoney($body['direct_cost_ex_vat'] ?? null);
        $mode=(string)($body['commission_mode'] ?? 'profit_percent');
        $fixed=$mode==='fixed' ? b2bMoney($body['fixed_commission'] ?? null) : null;
        $stage=(string)($body['stage'] ?? 'draft');
        $approved=!empty($body['approved']) ? 1 : 0;
        $costsVerified=!empty($body['costs_verified']) ? 1 : 0;
        if (!in_array($mode,['profit_percent','fixed'],true) ||
            !in_array($stage,['draft','invoiced','paid','cancelled'],true) ||
            $invoice==='' || $refId<=0) b2bFail(422,'invalid_sale');
        if ($approved && $stage!=='paid') b2bFail(422,'approval_requires_paid_invoice');
        if ($approved && !$costsVerified) b2bFail(422,'approval_requires_verified_costs');
        $s=$pdo->prepare('SELECT * FROM b2b_referrals WHERE id=? AND program_id=?');
        $s->execute([$refId,$programId]);
        $ref=$s->fetch(PDO::FETCH_ASSOC);
        if (!$ref) b2bFail(404,'referral_not_found');
        $candidate=['program_id'=>$programId,'revenue_ex_vat'=>$revenue,'direct_cost_ex_vat'=>$cost,
            'commission_mode'=>$mode,'fixed_commission'=>$fixed];
        $rows=b2bCommissionRows($pdo,$candidate,$ref,$program);
        $pdo->beginTransaction();
        if ($saleId>0) {
            $s=$pdo->prepare('SELECT id,referral_id FROM b2b_sales WHERE id=? AND program_id=? FOR UPDATE');
            $s->execute([$saleId,$programId]);
            $old=$s->fetch(PDO::FETCH_ASSOC);
            if (!$old || (int)$old['referral_id']!==$refId) { $pdo->rollBack(); b2bFail(409,'sale_referral_mismatch'); }
            $s=$pdo->prepare('SELECT id FROM b2b_commissions WHERE sale_id=? AND status="paid" LIMIT 1');
            $s->execute([$saleId]);
            if ($s->fetchColumn()) { $pdo->rollBack(); b2bFail(409,'paid_commission_locked'); }
            $s=$pdo->prepare('UPDATE b2b_sales SET invoice_reference=?,revenue_ex_vat=?,direct_cost_ex_vat=?,commission_mode=?,fixed_commission=?,stage=?,approved=?,costs_verified=?,updated_by_user_id=? WHERE id=? AND program_id=?');
            $s->execute([$invoice,$revenue,$cost,$mode,$fixed,$stage,$approved,$costsVerified,$uid,$saleId,$programId]);
            $pdo->prepare('DELETE FROM b2b_commissions WHERE sale_id=?')->execute([$saleId]);
        } else {
            $s=$pdo->prepare('INSERT INTO b2b_sales (program_id,referral_id,invoice_reference,revenue_ex_vat,direct_cost_ex_vat,commission_mode,fixed_commission,stage,approved,costs_verified,updated_by_user_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
            $s->execute([$programId,$refId,$invoice,$revenue,$cost,$mode,$fixed,$stage,$approved,$costsVerified,$uid]);
            $saleId=(int)$pdo->lastInsertId();
        }
        $ready=$stage==='paid' && $approved;
        $s=$pdo->prepare('INSERT INTO b2b_commissions (sale_id,partner_id,source_partner_id,kind,amount,status) VALUES (?,?,?,?,?,?)');
        foreach ($rows as $row) {
            if ($stage==='cancelled' || $row['amount']<=0) continue;
            $s->execute([$saleId,$row['partner_id'],(int)$ref['partner_id'],$row['kind'],$row['amount'],$ready?'ready':'pending']);
        }
        if ($ready) {
            $pdo->prepare('UPDATE b2b_referrals SET status="won" WHERE id=? AND program_id=?')->execute([$refId,$programId]);
        }
        b2bAudit($pdo,$uid,$ready?'approve_sale':'save_sale','sale',$saleId);
        $pdo->commit();
        jsonResponse(200,['ok'=>true,'sale_id'=>$saleId,'commission_total'=>$stage==='cancelled' ? 0 : round(array_sum(array_column($rows,'amount')),2),'status'=>$ready?'ready':'pending']);
    }
    if ($action==='mark_commission_paid') {
        if (!$admin) b2bFail(403,'admin_only');
        $id=(int)($body['commission_id'] ?? 0);
        $s=$pdo->prepare('UPDATE b2b_commissions c JOIN b2b_sales s ON s.id=c.sale_id SET c.status="paid",c.paid_at=NOW() WHERE c.id=? AND s.program_id=? AND c.status="ready" AND s.stage="paid" AND s.approved=1 AND s.costs_verified=1');
        $s->execute([$id,$programId]);
        if (!$s->rowCount()) b2bFail(409,'commission_not_ready');
        b2bAudit($pdo,$uid,'mark_paid','commission',$id);
        jsonResponse(200,['ok'=>true]);
    }
    b2bFail(404,'unknown_action');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('TrustAI B2B DB error: '.$e->getMessage());
    if (($e->errorInfo[1] ?? null)==1062) b2bFail(409,'duplicate');
    jsonResponse(503,['ok'=>false,'error'=>'database_error_or_migration_required']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('TrustAI B2B error: '.$e->getMessage());
    jsonResponse(500,['ok'=>false,'error'=>'unexpected_error']);
}
