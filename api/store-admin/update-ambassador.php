<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';

$user = requireRole('store_admin');
$storeId = (int)$user['store_id'];
$body = readJsonBody();
$ambassadorId = (int)($body['ambassador_id'] ?? $body['id'] ?? 0);

if ($ambassadorId <= 0) {
    jsonResponse(400, ['ok' => false, 'error' => 'ambassador_id_required']);
}

$stmt = $pdo->prepare('SELECT id, store_id FROM ambassadors WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $ambassadorId]);
$ambassador = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ambassador) {
    jsonResponse(404, ['ok' => false, 'error' => 'ambassador_not_found']);
}
if ((int)$ambassador['store_id'] !== $storeId) {
    jsonResponse(403, ['ok' => false, 'error' => 'store_mismatch']);
}

$allowed = ['commission_percent', 'name', 'email', 'phone'];
$set = [];
$params = ['id' => $ambassadorId];
foreach ($allowed as $field) {
    if (!array_key_exists($field, $body)) {
        continue;
    }
    $set[] = "$field = :$field";
    $params[$field] = $body[$field];
}

if (!$set) {
    jsonResponse(400, ['ok' => false, 'error' => 'no_fields_to_update']);
}

$updateStmt = $pdo->prepare('UPDATE ambassadors SET ' . implode(', ', $set) . ' WHERE id = :id');
$updateStmt->execute($params);

jsonResponse(200, ['ok' => true, 'ambassador_id' => $ambassadorId]);
