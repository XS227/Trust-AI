<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';

$body = $GLOBALS['__body'] ?? readJsonBody();
$ambassadorId = (int)($body['ambassador_id'] ?? 0);
$status = trim((string)($body['status'] ?? ''));
if (!in_array($status, ['approved', 'rejected', 'paused', 'pending'], true)) {
    jsonResponse(400, ['ok' => false, 'error' => 'invalid_status']);
}

$user = requireLogin();
if (!in_array($user['role'], ['super_admin', 'store_admin'], true)) {
    jsonResponse(403, ['ok' => false, 'error' => 'forbidden_role']);
}

$stmt = $pdo->prepare('SELECT id, store_id FROM ambassadors WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $ambassadorId]);
$ambassador = $stmt->fetch();
if (!$ambassador) {
    jsonResponse(404, ['ok' => false, 'error' => 'ambassador_not_found']);
}
if (!isSuperAdmin($user)) {
    requireStoreAccess((int)$ambassador['store_id']);
}

if ($status === 'approved') {
    $detailStmt = $pdo->prepare('SELECT id, store_id, name, email, referral_code FROM ambassadors WHERE id = :id LIMIT 1');
    $detailStmt->execute(['id' => $ambassadorId]);
    $details = $detailStmt->fetch();
    if ($details && trim((string)$details['referral_code']) === '') {
        $codeBase = strtolower((string)preg_replace('/[^a-z0-9]/i', '', (string)$details['name']));
        $codeBase = substr($codeBase ?: 'amb', 0, 8);
        $generatedCode = $codeBase . substr(md5((string)$details['email'] . microtime(true)), 0, 6);
        $codeStmt = $pdo->prepare('UPDATE ambassadors SET referral_code = :referral_code WHERE id = :id');
        $codeStmt->execute(['referral_code' => $generatedCode, 'id' => $ambassadorId]);
    }

    if ($details && trim((string)$details['email']) !== '') {
        $userStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $userStmt->execute(['email' => strtolower((string)$details['email'])]);
        $existingUser = $userStmt->fetch();
        if ($existingUser) {
            $linkUserStmt = $pdo->prepare('UPDATE users SET role = :role, store_id = :store_id, ambassador_id = :ambassador_id WHERE id = :id');
            $linkUserStmt->execute([
                'role' => 'ambassador',
                'store_id' => (int)$details['store_id'],
                'ambassador_id' => $ambassadorId,
                'id' => (int)$existingUser['id'],
            ]);
            $ambLinkStmt = $pdo->prepare('UPDATE ambassadors SET user_id = :user_id WHERE id = :id');
            $ambLinkStmt->execute(['user_id' => (int)$existingUser['id'], 'id' => $ambassadorId]);
        }
    }
}

$update = $pdo->prepare('UPDATE ambassadors SET status = :status, approved_at = CASE WHEN :status = "approved" THEN NOW() ELSE approved_at END WHERE id = :id');
$update->execute(['status' => $status, 'id' => $ambassadorId]);

$detail = $pdo->prepare('SELECT id, store_id, user_id, name, email, phone, referral_code, status, commission_percent, created_at, approved_at FROM ambassadors WHERE id = :id LIMIT 1');
$detail->execute(['id' => $ambassadorId]);
jsonResponse(200, ['ok' => true, 'ambassador' => $detail->fetch(PDO::FETCH_ASSOC)]);
