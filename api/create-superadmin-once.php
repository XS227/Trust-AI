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

$configFile = realpath(__DIR__ . '/../inc/config.php') ?: (__DIR__ . '/../inc/config.php');
require_once $configFile;

$email = 'admin@trustai.no';
$temporaryPassword = 'TrustAiAdmin!2026';
$passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);

if (!is_string($passwordHash) || $passwordHash === '') {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'password_hash_failed']);
    exit;
}

$debug = [
    'db_connected' => false,
    'users_table_exists' => false,
    'detected_columns' => [],
    'missing_required_columns' => [],
    'pdo_mysql_error' => null,
    'config_file_used' => $configFile,
];

$requiredColumnGroups = [
    'email_or_e_post' => ['email', 'e_post'],
    'password_or_password_hash' => ['password', 'password_hash'],
];

$missingColumnGroups = static function (array $columns) use ($requiredColumnGroups): array {
    $missing = [];
    foreach ($requiredColumnGroups as $groupName => $candidates) {
        $found = false;
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $missing[] = $groupName;
        }
    }
    return $missing;
};

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $debug['db_connected'] = true;

    $tableExistsStmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :schema AND table_name = 'users'");
    $tableExistsStmt->execute(['schema' => DB_NAME]);
    $debug['users_table_exists'] = ((int) $tableExistsStmt->fetchColumn()) > 0;

    if (!$debug['users_table_exists']) {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'error' => 'users_table_missing',
            'message' => 'users-tabellen finnes ikke. Importer SQL under og prøv igjen.',
            'required_sql' => "CREATE TABLE `users` (\n"
                . "  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,\n"
                . "  `email` VARCHAR(191) NOT NULL,\n"
                . "  `password_hash` VARCHAR(255) NOT NULL,\n"
                . "  `role` VARCHAR(50) NULL,\n"
                . "  `user_role` VARCHAR(50) NULL,\n"
                . "  `type` VARCHAR(50) NULL,\n"
                . "  `user_type` VARCHAR(50) NULL,\n"
                . "  `is_super_admin` TINYINT(1) NOT NULL DEFAULT 0,\n"
                . "  `status` VARCHAR(50) NULL,\n"
                . "  `approved` TINYINT(1) NOT NULL DEFAULT 0,\n"
                . "  `created_at` DATETIME NULL,\n"
                . "  `updated_at` DATETIME NULL,\n"
                . "  PRIMARY KEY (`id`),\n"
                . "  UNIQUE KEY `uniq_users_email` (`email`)\n"
                . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
            'debug' => $debug,
        ]);
        exit;
    }

    $columns = $pdo->query('DESCRIBE users')->fetchAll(PDO::FETCH_ASSOC);
    $detectedColumns = array_values(array_map(static fn(array $col): string => (string) $col['Field'], $columns));
    $debug['detected_columns'] = $detectedColumns;
    $debug['missing_required_columns'] = $missingColumnGroups($detectedColumns);

    if ($debug['missing_required_columns'] !== []) {
        throw new RuntimeException('users-tabellen mangler obligatoriske kolonner: ' . implode(', ', $debug['missing_required_columns']));
    }

    $has = static fn(string $col): bool => in_array($col, $detectedColumns, true);
    $emailColumn = $has('email') ? 'email' : 'e_post';
    $passwordColumn = $has('password_hash') ? 'password_hash' : 'password';

    $updatableCols = [];
    $insertCols = [];
    $params = [];

    $updatableCols[] = "`{$emailColumn}` = :email";
    $insertCols[$emailColumn] = ':email';
    $params['email'] = $email;

    $updatableCols[] = "`{$passwordColumn}` = :password_value";
    $insertCols[$passwordColumn] = ':password_value';
    $params['password_value'] = $passwordHash;

    foreach (['role', 'user_role', 'type', 'user_type'] as $roleCol) {
        if ($has($roleCol)) {
            $updatableCols[] = "`{$roleCol}` = :{$roleCol}";
            $insertCols[$roleCol] = ':' . $roleCol;
            $params[$roleCol] = 'super_admin';
        }
    }

    if ($has('is_super_admin')) {
        $updatableCols[] = '`is_super_admin` = :is_super_admin';
        $insertCols['is_super_admin'] = ':is_super_admin';
        $params['is_super_admin'] = 1;
    }

    if ($has('status')) {
        $updatableCols[] = '`status` = :status';
        $insertCols['status'] = ':status';
        $params['status'] = 'active';
    }

    if ($has('approved')) {
        $updatableCols[] = '`approved` = :approved';
        $insertCols['approved'] = ':approved';
        $params['approved'] = 1;
    }

    if ($has('created_at')) {
        $updatableCols[] = '`created_at` = COALESCE(`created_at`, :created_at)';
        $insertCols['created_at'] = ':created_at';
        $params['created_at'] = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }

    if ($has('updated_at')) {
        $updatableCols[] = '`updated_at` = :updated_at';
        $insertCols['updated_at'] = ':updated_at';
        $params['updated_at'] = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }

    $pdo->beginTransaction();

    $check = $pdo->prepare("SELECT id FROM users WHERE `{$emailColumn}` = :email LIMIT 1");
    $check->execute(['email' => $email]);
    $exists = $check->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        $sql = 'UPDATE users SET ' . implode(', ', $updatableCols) . " WHERE `{$emailColumn}` = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } else {
        $insertColNames = array_map(static fn(string $name): string => "`{$name}`", array_keys($insertCols));
        $insertPlaceholders = array_values($insertCols);
        $sql = 'INSERT INTO users (' . implode(', ', $insertColNames) . ') VALUES (' . implode(', ', $insertPlaceholders) . ')';
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

    $debug['pdo_mysql_error'] = $e->getMessage();

    if ($debug['missing_required_columns'] === [] && $debug['detected_columns'] !== []) {
        $debug['missing_required_columns'] = $missingColumnGroups($debug['detected_columns']);
    }

    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'seed_failed',
        'debug' => $debug,
    ]);
}
