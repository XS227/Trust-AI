<?php
declare(strict_types=1);

/**
 * Simple DB-backed rate limiting for login-style endpoints.
 *
 * MEDIUM finding from the 2026-07-12 security audit: api/auth/login.php had
 * no attempt-tracking of any kind — unlimited password guesses against any
 * known email, no delay, no lockout. Self-healing schema (CREATE TABLE IF
 * NOT EXISTS at request time) matches the existing pattern in
 * api/auth/forgot-password.php rather than requiring a separate migration
 * step.
 */

function rateLimitEnsureSchema(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        email VARCHAR(191) NOT NULL,
        ip_address VARCHAR(64) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_login_attempts_email (email, created_at),
        KEY idx_login_attempts_ip (ip_address, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $checked = true;
}

function rateLimitClientIp(): string
{
    return (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
}

/**
 * Returns null if the request may proceed, or an array
 * ['retry_after_seconds' => int] if it's currently locked out.
 *
 * Locks out on 5 failures within a 15-minute window, tracked by email AND
 * by IP independently (whichever trips first blocks the request) — email
 * tracking stops credential-stuffing against one account from any IP;
 * IP tracking stops one attacker from spraying many emails from one
 * address. A shared IP (office NAT, etc.) can still lock out on its own
 * count without punishing a different email's independent attempts.
 */
function rateLimitCheck(PDO $pdo, string $email): ?array
{
    rateLimitEnsureSchema($pdo);
    $ip = rateLimitClientIp();
    $windowStart = date('Y-m-d H:i:s', time() - 15 * 60);

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS c, MIN(created_at) AS oldest
         FROM login_attempts
         WHERE created_at > :window AND (email = :email OR ip_address = :ip)'
    );
    $stmt->execute(['window' => $windowStart, 'email' => $email, 'ip' => $ip]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ((int)($row['c'] ?? 0) >= 5) {
        $oldestTs = strtotime((string)$row['oldest']);
        $retryAfter = max(1, ($oldestTs + 15 * 60) - time());
        return ['retry_after_seconds' => $retryAfter];
    }
    return null;
}

function rateLimitRecordFailure(PDO $pdo, string $email): void
{
    rateLimitEnsureSchema($pdo);
    $stmt = $pdo->prepare('INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip)');
    $stmt->execute(['email' => $email, 'ip' => rateLimitClientIp()]);
}

function rateLimitClearOnSuccess(PDO $pdo, string $email): void
{
    rateLimitEnsureSchema($pdo);
    $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE email = :email');
    $stmt->execute(['email' => $email]);
}
