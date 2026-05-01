<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';

$user = requireRole('store_admin');
$storeId = (int)$user['store_id'];
$body = readJsonBody();
$payoutId = (int)($body['payout_id'] ?? $body['id'] ?? 0);
$status = trim((string)($body['status'] ?? ''));

if ($payoutId <= 0) {
    jsonResponse(400, ['ok' => false, 'error' => 'payout_id_required']);
}
if (!in_array($status, ['paid', 'rejected'], true)) {
    jsonResponse(400, ['ok' => false, 'error' => 'invalid_status']);
}

$payoutStmt = $pdo->prepare('SELECT id, store_id FROM payouts WHERE id = :id LIMIT 1');
$payoutStmt->execute(['id' => $payoutId]);
$payout = $payoutStmt->fetch(PDO::FETCH_ASSOC);
if (!$payout) {
    jsonResponse(404, ['ok' => false, 'error' => 'payout_not_found']);
}
if ((int)$payout['store_id'] !== $storeId) {
    jsonResponse(403, ['ok' => false, 'error' => 'store_mismatch']);
}

$updateStmt = $pdo->prepare('UPDATE payouts SET status = :status, paid_at = CASE WHEN :status = "paid" THEN NOW() ELSE paid_at END WHERE id = :id');
$updateStmt->execute(['status' => $status, 'id' => $payoutId]);

jsonResponse(200, ['ok' => true, 'payout_id' => $payoutId, 'status' => $status]);
