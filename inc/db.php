<?php

declare(strict_types=1);

require __DIR__ . '/config.php';

$trustAiDbValueIsPlaceholder = static function (?string $value): bool {
    $normalized = strtolower(trim((string)$value));
    if ($normalized === '') {
        return true;
    }

    $placeholders = [
        'your_db',
        'your_user',
        'your_pass',
        'changeme',
        'change_me',
        'password',
        'secret',
        'example',
    ];

    return in_array($normalized, $placeholders, true);
};

if (
    $trustAiDbValueIsPlaceholder(DB_HOST)
    || $trustAiDbValueIsPlaceholder(DB_NAME)
    || $trustAiDbValueIsPlaceholder(DB_USER)
    || $trustAiDbValueIsPlaceholder(DB_PASS)
) {
    throw new RuntimeException('db_not_configured');
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );
} catch (Throwable $e) {
    error_log('inc/db.php: PDO connection failed: ' . $e->getMessage());
    throw new RuntimeException('database_connection_failed');
}
