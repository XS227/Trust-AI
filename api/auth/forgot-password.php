<?php
declare(strict_types=1);
require_once __DIR__ . '/_auth_common.php';

$body = readJsonBody();
$email = strtolower(trim((string)($body['email'] ?? '')));
$generic = 'Hvis e-posten er registrert, sender vi deg en melding.';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !$pdo instanceof PDO) {
    jsonResponse(200, ['ok' => true, 'message' => $generic]);
}

$pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_token_hash (token_hash)
)");

$stmt = $pdo->prepare('SELECT id FROM users WHERE email=:email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $ins = $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL 30 MINUTE))');
    $ins->execute(['user_id' => (int)$user['id'], 'token_hash' => $tokenHash]);

    $base = trustaiGetEnv('APP_BASE_URL', 'https://trustai.no');
    $url = rtrim($base, '/') . '/reset-password.html?token=' . urlencode($token);
    $subject = 'Tilbakestill passord';
    $message = "Hei!\n\nKlikk lenken for å sette nytt passord (gyldig i 30 minutter):\n{$url}\n\nHvis du ikke ba om dette kan du ignorere e-posten.";
    $headers = 'From: no-reply@trustai.no' . "\r\n" . 'Content-Type: text/plain; charset=UTF-8';
    @mail($email, $subject, $message, $headers);
}

jsonResponse(200, ['ok' => true, 'message' => $generic]);
