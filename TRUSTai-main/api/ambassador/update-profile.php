<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';

$user = requireRole('ambassador');
$ambassadorId = (int)$user['ambassador_id'];
$storeId = (int)$user['store_id'];
requireAmbassadorAccess($ambassadorId);
$body = readJsonBody();

$set = [];
$params = ['id' => $ambassadorId, 'store_id' => $storeId];
foreach (['name', 'email', 'phone'] as $field) {
    if (array_key_exists($field, $body)) {
        $set[] = "$field = :$field";
        $params[$field] = trim((string)$body[$field]);
    }
}
if (!$set) {
    jsonResponse(400, ['ok' => false, 'error' => 'no_fields_to_update']);
}

$stmt = $pdo->prepare('UPDATE ambassadors SET ' . implode(', ', $set) . ' WHERE id = :id AND store_id = :store_id');
$stmt->execute($params);

if (array_key_exists('email', $body) && trim((string)$body['email']) !== '') {
    $userUpdate = $pdo->prepare('UPDATE users SET email = :email WHERE ambassador_id = :ambassador_id AND role = :role');
    $userUpdate->execute(['email' => strtolower(trim((string)$body['email'])), 'ambassador_id' => $ambassadorId, 'role' => 'ambassador']);
}

jsonResponse(200, ['ok' => true, 'ambassador_id' => $ambassadorId]);
