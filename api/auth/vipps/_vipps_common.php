<?php
declare(strict_types=1);

require_once __DIR__ . '/../_auth_common.php';

function trustaiVippsConfigValue(string $envKey, string $constantName, string $default = ''): string
{
    $value = getenv($envKey);
    if ($value !== false && $value !== '') {
        return (string)$value;
    }
    if (defined($constantName)) {
        $constValue = (string)constant($constantName);
        if ($constValue !== '') {
            return $constValue;
        }
    }
    return $default;
}

function trustaiVippsConfig(): array
{
    $env = strtolower(trustaiVippsConfigValue('VIPPS_ENV', 'VIPPS_ENV', 'test'));
    if ($env !== 'prod') {
        $env = 'test';
    }
    $wellKnown = $env === 'prod'
        ? 'https://api.vipps.no/access-management-1.0/access/.well-known/openid-configuration'
        : 'https://apitest.vipps.no/access-management-1.0/access/.well-known/openid-configuration';

    return [
        'env' => $env,
        'client_id' => trustaiVippsConfigValue('VIPPS_CLIENT_ID', 'VIPPS_CLIENT_ID'),
        'client_secret' => trustaiVippsConfigValue('VIPPS_CLIENT_SECRET', 'VIPPS_CLIENT_SECRET'),
        'subscription_key' => trustaiVippsConfigValue('VIPPS_SUBSCRIPTION_KEY', 'VIPPS_SUBSCRIPTION_KEY'),
        'msn' => trustaiVippsConfigValue('VIPPS_MSN', 'VIPPS_MSN'),
        'redirect_uri' => trustaiVippsConfigValue(
            'VIPPS_REDIRECT_URI',
            'VIPPS_REDIRECT_URI',
            'https://trustai.no/api/auth/vipps/callback.php'
        ),
        'well_known' => $wellKnown,
    ];
}

function trustaiVippsRequireConfig(): array
{
    $cfg = trustaiVippsConfig();
    $missing = [];
    foreach (['client_id', 'client_secret', 'subscription_key', 'msn', 'redirect_uri'] as $key) {
        if (($cfg[$key] ?? '') === '') {
            $missing[] = strtoupper('VIPPS_' . ($key === 'msn' ? 'MSN' : $key));
        }
    }
    if ($missing) {
        error_log('Vipps: missing config keys: ' . implode(', ', $missing));
        header('Location: /login.html?error=vipps_not_configured');
        exit;
    }
    return $cfg;
}

function trustaiVippsSystemHeaders(array $cfg): array
{
    return [
        'Ocp-Apim-Subscription-Key: ' . $cfg['subscription_key'],
        'Merchant-Serial-Number: ' . $cfg['msn'],
        'Vipps-System-Name: TrustAI',
        'Vipps-System-Version: 1.0.0',
        'Vipps-System-Plugin-Name: TrustAI-Login',
        'Vipps-System-Plugin-Version: 1.0.0',
    ];
}

function trustaiVippsHttpGetJson(string $url, array $headers = [], int $timeout = 15): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $headers),
    ]);
    $body = curl_exec($ch);
    $err = curl_error($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false) {
        return ['ok' => false, 'http' => 0, 'error' => $err, 'json' => null];
    }
    $json = json_decode((string)$body, true);
    return [
        'ok' => $http >= 200 && $http < 300 && is_array($json),
        'http' => $http,
        'json' => is_array($json) ? $json : null,
        'body' => $body,
    ];
}

function trustaiVippsDiscover(array $cfg): ?array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }
    $result = trustaiVippsHttpGetJson($cfg['well_known']);
    if (!$result['ok']) {
        return null;
    }
    $cache = $result['json'];
    return $cache;
}

function trustaiVippsBase64UrlEncode(string $bin): string
{
    return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
}

function trustaiVippsEnsureSchema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    try {
        $pdo->exec(
            "ALTER TABLE users
              ADD COLUMN IF NOT EXISTS provider VARCHAR(40) DEFAULT NULL,
              ADD COLUMN IF NOT EXISTS provider_id VARCHAR(190) DEFAULT NULL,
              ADD COLUMN IF NOT EXISTS vipps_sub VARCHAR(190) DEFAULT NULL,
              ADD COLUMN IF NOT EXISTS full_name VARCHAR(191) DEFAULT NULL,
              ADD COLUMN IF NOT EXISTS phone_number VARCHAR(60) DEFAULT NULL,
              ADD COLUMN IF NOT EXISTS status VARCHAR(40) NOT NULL DEFAULT 'active',
              ADD COLUMN IF NOT EXISTS role VARCHAR(40) DEFAULT NULL"
        );
    } catch (Throwable $e) {
        error_log('Vipps ensure schema (columns) failed: ' . $e->getMessage());
    }
    try {
        $pdo->exec("ALTER TABLE users ADD UNIQUE KEY IF NOT EXISTS uniq_users_vipps_sub (vipps_sub)");
    } catch (Throwable $e) {
        // Index may exist already; ignore.
    }
    $done = true;
}

function trustaiVippsNormalizePhone(?string $raw): string
{
    if ($raw === null) {
        return '';
    }
    $phone = preg_replace('/\s+/', '', trim((string)$raw));
    return (string)$phone;
}
