<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';

requireRole('super_admin');
$body = readJsonBody();
$payoutId = (int)($body['payout_id'] ?? $body['id'] ?? 0);
$status = trim((string)($body['status'] ?? ''));

if ($payoutId <= 0) {
    jsonResponse(400, ['ok' => false, 'error' => 'payout_id_required']);
}
if (!in_array($status, ['paid', 'rejected', 'approved', 'requested'], true)) {
    jsonResponse(400, ['ok' => false, 'error' => 'invalid_status']);
}

$stmt = $pdo->prepare('UPDATE payouts SET status = :status, paid_at = CASE WHEN :status = "paid" THEN NOW() ELSE paid_at END WHERE id = :id');
$stmt->execute(['status' => $status, 'id' => $payoutId]);

jsonResponse(200, ['ok' => true, 'payout_id' => $payoutId, 'status' => $status]);
