<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';

$user = requireLogin();
$role = $user['role'];

if (!in_array($role, ['super_admin', 'store_admin', 'ambassador'], true)) {
    jsonResponse(403, ['ok' => false, 'error' => 'forbidden_role']);
}

$body = readJsonBody();
$leadId  = (int)($body['lead_id'] ?? 0);
$noteText = trim((string)($body['note_text'] ?? ''));
$visibleToAmbassador = !empty($body['visible_to_ambassador']) ? 1 : 0;

if ($leadId <= 0) {
    jsonResponse(400, ['ok' => false, 'error' => 'missing_lead_id']);
}
if ($noteText === '') {
    jsonResponse(422, ['ok' => false, 'error' => 'note_text_required']);
}

$stmt = $pdo->prepare('SELECT store_id, ambassador_id FROM leads WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $leadId]);
$lead = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lead) {
    jsonResponse(404, ['ok' => false, 'error' => 'not_found']);
}

if ($role === 'store_admin' && (int)$lead['store_id'] !== (int)($user['store_id'] ?? 0)) {
    jsonResponse(403, ['ok' => false, 'error' => 'forbidden_store']);
}
if ($role === 'ambassador' && (int)$lead['ambassador_id'] !== (int)($user['ambassador_id'] ?? 0)) {
    jsonResponse(403, ['ok' => false, 'error' => 'forbidden_ambassador']);
}

$pdo->prepare("INSERT INTO lead_notes (lead_id, user_id, note_text, visible_to_ambassador)
VALUES (:lead_id, :user_id, :note_text, :visible)")
->execute([
    'lead_id'  => $leadId,
    'user_id'  => (int)$user['id'],
    'note_text'=> $noteText,
    'visible'  => $visibleToAmbassador,
]);

$noteId = (int)$pdo->lastInsertId();

$pdo->prepare("INSERT INTO lead_history (lead_id, user_id, action_type, new_value, visible_to_ambassador)
VALUES (:lead_id, :user_id, 'note_added', :note_text, :visible)")
->execute([
    'lead_id'  => $leadId,
    'user_id'  => (int)$user['id'],
    'note_text'=> $noteText,
    'visible'  => $visibleToAmbassador,
]);

jsonResponse(201, ['ok' => true, 'note_id' => $noteId]);
