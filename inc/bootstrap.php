<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (!function_exists('trustaiGetAppBool')) {
    function trustaiGetAppBool(string $key, bool $default = false): bool
    {
        $env = getenv($key);
        if ($env !== false && $env !== '') {
            return in_array(strtolower(trim($env)), ['1', 'true', 'yes', 'on'], true);
        }

        if (defined($key)) {
            return (bool)constant($key);
        }

        return $default;
    }
}

if (!function_exists('trustaiIsHttpsRequest')) {
    function trustaiIsHttpsRequest(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        $proto = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($proto === 'https') {
            return true;
        }

        return ((int)($_SERVER['SERVER_PORT'] ?? 80)) === 443;
    }
}

if (!function_exists('trustaiConfigureSession')) {
    function trustaiConfigureSession(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $https = trustaiIsHttpsRequest();
        $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        $host = preg_replace('/:\\d+$/', '', $host);
        $domain = '';
        if ($host === 'trustai.no' || str_ends_with($host, '.trustai.no')) {
            $domain = 'trustai.no';
        }

        $opts = [
            'lifetime' => 0,
            'path' => '/',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ];
        if ($domain !== '') {
            $opts['domain'] = $domain;
        }

        session_name('TRUSTAISESSID');
        session_set_cookie_params($opts);
    }
}
