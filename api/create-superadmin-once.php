<?php
/**
 * ENGANGS-SEED FOR SUPERADMIN
 *
 * Etter vellykket kjøring blir scriptet låst med LOCK-fil og kan ikke kjøres igjen.
 * For å disable/slette: fjern denne filen fra serveren (anbefalt), eller behold LOCK-filen
 * `api/create-superadmin-once.lock` slik at endpointet forblir blokkert.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$lockFile = __DIR__ . '/create-superadmin-once.lock';
if (is_file($lockFile)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'locked']);
    exit;
}

require_once __DIR__ . '/../inc/config.php';

$email = 'admin@trustai.no';
$temporaryPassword = 'TrustAiAdmin!2026';
$passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);

if (!is_string($passwordHash) || $passwordHash === '') {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'password_hash_failed']);
    exit;
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN, 0);
    $has = static fn(string $col): bool => in_array($col, $columns, true);

    $updates = ['password_hash = :password_hash', 'role = :role'];
    $params = [
        'email' => $email,
        'password_hash' => $passwordHash,
        'role' => 'super_admin',
    ];

    if ($has('status')) {
        $updates[] = 'status = :status';
        $params['status'] = 'active';
    }
    if ($has('user_type')) {
        $updates[] = 'user_type = :user_type';
        $params['user_type'] = 'super_admin';
    }
    if ($has('is_super_admin')) {
        $updates[] = 'is_super_admin = :is_super_admin';
        $params['is_super_admin'] = 1;
    }

    $pdo->beginTransaction();

    $check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $check->execute(['email' => $email]);
    $exists = $check->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE email = :email';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } else {
        $insertCols = ['email', 'password_hash', 'role'];
        $insertVals = [':email', ':password_hash', ':role'];

        if ($has('status')) {
            $insertCols[] = 'status';
            $insertVals[] = ':status';
        }
        if ($has('user_type')) {
            $insertCols[] = 'user_type';
            $insertVals[] = ':user_type';
        }
        if ($has('is_super_admin')) {
            $insertCols[] = 'is_super_admin';
            $insertVals[] = ':is_super_admin';
        }

        $sql = 'INSERT INTO users (' . implode(', ', $insertCols) . ') VALUES (' . implode(', ', $insertVals) . ')';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    $pdo->commit();

    $lockWritten = @file_put_contents($lockFile, (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM) . PHP_EOL);
    if ($lockWritten === false) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'lock_write_failed']);
        exit;
    }

    echo json_encode(['ok' => true, 'email' => $email]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'seed_failed']);
}
