<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';

$user = requireLogin();
$role = $user['role'];

if (!in_array($role, ['super_admin', 'store_admin'], true)) {
    jsonResponse(403, ['ok' => false, 'error' => 'forbidden_role']);
}

$body = readJsonBody();

$storeId      = (int)($body['store_id'] ?? 0);
$ambassadorId = isset($body['ambassador_id']) && $body['ambassador_id'] !== '' ? (int)$body['ambassador_id'] : null;
$companyName  = trim((string)($body['company_name'] ?? ''));
$contactPerson = trim((string)($body['contact_person'] ?? ''));
$contactEmail  = trim((string)($body['contact_email'] ?? ''));
$contactPhone  = trim((string)($body['contact_phone'] ?? ''));
$source        = trim((string)($body['source'] ?? ''));
$offerAmount   = isset($body['offer_amount']) && $body['offer_amount'] !== '' ? (float)$body['offer_amount'] : null;
$commPct       = isset($body['commission_percent']) && $body['commission_percent'] !== '' ? (float)$body['commission_percent'] : null;
$followUpDate  = !empty($body['follow_up_date']) ? $body['follow_up_date'] : null;

if ($companyName === '') {
    jsonResponse(422, ['ok' => false, 'error' => 'company_name_required']);
}
if ($storeId <= 0) {
    jsonResponse(422, ['ok' => false, 'error' => 'store_id_required']);
}

if ($role === 'store_admin' && (int)($user['store_id'] ?? 0) !== $storeId) {
    jsonResponse(403, ['ok' => false, 'error' => 'forbidden_store']);
}

$commAmount = ($offerAmount !== null && $commPct !== null)
    ? round($offerAmount * $commPct / 100, 2)
    : null;

$ins = $pdo->prepare("INSERT INTO leads
    (store_id, ambassador_id, company_name, contact_person, contact_email, contact_phone,
     source, offer_amount, commission_percent, commission_amount, follow_up_date, status)
VALUES
    (:store_id, :ambassador_id, :company_name, :contact_person, :contact_email, :contact_phone,
     :source, :offer_amount, :commission_percent, :commission_amount, :follow_up_date, 'open')");

$ins->execute([
    'store_id'          => $storeId,
    'ambassador_id'     => $ambassadorId,
    'company_name'      => $companyName,
    'contact_person'    => $contactPerson,
    'contact_email'     => $contactEmail,
    'contact_phone'     => $contactPhone,
    'source'            => $source,
    'offer_amount'      => $offerAmount,
    'commission_percent'=> $commPct,
    'commission_amount' => $commAmount,
    'follow_up_date'    => $followUpDate,
]);

$leadId = (int)$pdo->lastInsertId();

$pdo->prepare("INSERT INTO lead_history (lead_id, user_id, action_type, visible_to_ambassador)
VALUES (:lead_id, :user_id, 'created', 0)")->execute([
    'lead_id' => $leadId,
    'user_id' => (int)$user['id'],
]);

jsonResponse(201, ['ok' => true, 'id' => $leadId]);
