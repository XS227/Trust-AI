<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';

$user = requireLogin();
$role = $user['role'];
$leadId = (int)($_GET['id'] ?? 0);

if ($leadId <= 0) {
    jsonResponse(400, ['ok' => false, 'error' => 'missing_id']);
}

if (!in_array($role, ['super_admin', 'store_admin', 'ambassador'], true)) {
    jsonResponse(403, ['ok' => false, 'error' => 'forbidden_role']);
}

$stmt = $pdo->prepare("SELECT l.*,
    COALESCE(a.name, a.ambassador_name) AS ambassador_name,
    s.name AS store_name
FROM leads l
LEFT JOIN ambassadors a ON l.ambassador_id = a.id
LEFT JOIN stores s ON l.store_id = s.id
WHERE l.id = :id LIMIT 1");
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

$histWhere = $role === 'ambassador' ? 'AND h.visible_to_ambassador = 1' : '';
$histStmt = $pdo->prepare("SELECT h.*, u.email AS user_email
FROM lead_history h
LEFT JOIN users u ON h.user_id = u.id
WHERE h.lead_id = :lead_id $histWhere
ORDER BY h.created_at ASC");
$histStmt->execute(['lead_id' => $leadId]);
$history = $histStmt->fetchAll(PDO::FETCH_ASSOC);

$noteWhere = $role === 'ambassador' ? 'AND n.visible_to_ambassador = 1' : '';
$noteStmt = $pdo->prepare("SELECT n.*, u.email AS user_email
FROM lead_notes n
LEFT JOIN users u ON n.user_id = u.id
WHERE n.lead_id = :lead_id $noteWhere
ORDER BY n.created_at ASC");
$noteStmt->execute(['lead_id' => $leadId]);
$notes = $noteStmt->fetchAll(PDO::FETCH_ASSOC);

jsonResponse(200, ['ok' => true, 'lead' => $lead, 'history' => $history, 'notes' => $notes]);
