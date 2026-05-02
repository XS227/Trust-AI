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
        $value = trim((string)$body[$field]);
        if ($field === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(400, ['ok' => false, 'error' => 'invalid_email', 'message' => 'Ugyldig e-postadresse']);
        }
        $set[] = "$field = :$field";
        $params[$field] = $value;
    }
}

// Håndter passord-endring
$newPassword = isset($body['password']) ? (string)$body['password'] : '';
$updatePassword = false;
if ($newPassword !== '') {
    if (strlen($newPassword) < 8) {
        jsonResponse(400, ['ok' => false, 'error' => 'password_too_short', 'message' => 'Passord må være minst 8 tegn']);
    }
    $updatePassword = true;
}

if (!$set && !$updatePassword) {
    jsonResponse(400, ['ok' => false, 'error' => 'no_fields_to_update', 'message' => 'Ingen felter å oppdatere']);
}

try {
    $pdo->beginTransaction();

    if ($set) {
        $stmt = $pdo->prepare('UPDATE ambassadors SET ' . implode(', ', $set) . ' WHERE id = :id AND store_id = :store_id');
        $stmt->execute($params);
    }

    // Oppdater e-post i users-tabell hvis endret
    if (array_key_exists('email', $body) && trim((string)$body['email']) !== '') {
        $userUpdate = $pdo->prepare('UPDATE users SET email = :email, updated_at = NOW() WHERE ambassador_id = :ambassador_id AND role = :role');
        $userUpdate->execute([
            'email' => strtolower(trim((string)$body['email'])),
            'ambassador_id' => $ambassadorId,
            'role' => 'ambassador',
        ]);
    }

    // Oppdater passord hvis satt
    if ($updatePassword) {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $pwUpdate = $pdo->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE ambassador_id = :ambassador_id AND role = :role');
        $pwUpdate->execute([
            'hash' => $hash,
            'ambassador_id' => $ambassadorId,
            'role' => 'ambassador',
        ]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('update-profile failed: ' . $e->getMessage());
    jsonResponse(500, ['ok' => false, 'error' => 'update_failed', 'message' => 'Kunne ikke oppdatere profil']);
}

jsonResponse(200, [
    'ok' => true,
    'ambassador_id' => $ambassadorId,
    'message' => 'Profil oppdatert' . ($updatePassword ? ' (inkl. passord)' : '') . '.',
]);
