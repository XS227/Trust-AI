<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';

$user = requireLogin();
$role = $user['role'];

if (!in_array($role, ['super_admin', 'store_admin'], true)) {
    jsonResponse(403, ['ok' => false, 'error' => 'forbidden_role']);
}

$body = readJsonBody();
$leadId = (int)($body['id'] ?? 0);

if ($leadId <= 0) {
    jsonResponse(400, ['ok' => false, 'error' => 'missing_id']);
}

$stmt = $pdo->prepare('SELECT * FROM leads WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $leadId]);
$old = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$old) {
    jsonResponse(404, ['ok' => false, 'error' => 'not_found']);
}

if ($role === 'store_admin' && (int)$old['store_id'] !== (int)($user['store_id'] ?? 0)) {
    jsonResponse(403, ['ok' => false, 'error' => 'forbidden_store']);
}

$comment             = trim((string)($body['comment'] ?? ''));
$visibleToAmbassador = !empty($body['visible_to_ambassador']) ? 1 : 0;

$updatable = [
    'company_name', 'contact_person', 'contact_email', 'contact_phone',
    'status', 'source', 'offer_amount', 'commission_percent', 'follow_up_date',
    'ambassador_id', 'internal_notes',
];

$changes = [];
$newVals = [];

foreach ($updatable as $field) {
    if (!array_key_exists($field, $body)) {
        continue;
    }
    $newRaw = $body[$field] === '' ? null : $body[$field];
    $oldVal = $old[$field] ?? null;
    $newVal = ($newRaw !== null) ? (string)$newRaw : null;
    $oldStr = ($oldVal !== null) ? (string)$oldVal : null;

    if ($newVal !== $oldStr) {
        $changes[$field] = ['old' => $oldStr, 'new' => $newVal];
        $newVals[$field] = ($field === 'offer_amount' || $field === 'commission_percent')
            ? ($newRaw !== null ? (float)$newRaw : null)
            : $newRaw;
    }
}

if (isset($changes['status']) && $comment === '') {
    jsonResponse(422, ['ok' => false, 'error' => 'comment_required_for_status_change']);
}

if (isset($newVals['offer_amount']) || isset($newVals['commission_percent'])) {
    $offerAmt = isset($newVals['offer_amount'])
        ? $newVals['offer_amount']
        : (float)($old['offer_amount'] ?? 0);
    $commPct = isset($newVals['commission_percent'])
        ? $newVals['commission_percent']
        : (float)($old['commission_percent'] ?? 0);
    if ($offerAmt !== null && $commPct !== null) {
        $newVals['commission_amount'] = round((float)$offerAmt * (float)$commPct / 100, 2);
    }
}

if (empty($changes)) {
    jsonResponse(200, ['ok' => true, 'message' => 'no_changes']);
}

$setClauses = [];
$updateParams = ['id' => $leadId];
foreach ($newVals as $field => $val) {
    $setClauses[] = "$field = :upd_$field";
    $updateParams["upd_$field"] = $val;
}

if (!empty($setClauses)) {
    $pdo->prepare("UPDATE leads SET " . implode(', ', $setClauses) . " WHERE id = :id")
        ->execute($updateParams);
}

$histStmt = $pdo->prepare("INSERT INTO lead_history
    (lead_id, user_id, action_type, field_name, old_value, new_value, comment, visible_to_ambassador)
VALUES
    (:lead_id, :user_id, :action_type, :field_name, :old_value, :new_value, :comment, :visible)");

foreach ($changes as $field => $vals) {
    $actionType = $field === 'status' ? 'status_change' : 'field_update';
    $histStmt->execute([
        'lead_id'    => $leadId,
        'user_id'    => (int)$user['id'],
        'action_type'=> $actionType,
        'field_name' => $field,
        'old_value'  => $vals['old'],
        'new_value'  => $vals['new'],
        'comment'    => $comment ?: null,
        'visible'    => $visibleToAmbassador,
    ]);
}

jsonResponse(200, ['ok' => true, 'changes' => count($changes)]);
