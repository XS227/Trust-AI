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
    // The MySQL flavour on the live host (< 8.0.29) does not understand
    // `ADD COLUMN IF NOT EXISTS`, so column ensure is handled by the dedicated
    // migration runner. See database/migrations/2026-05-10_vipps_*.sql.
    static $done = false;
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

/**
 * Parse Vipps userinfo birthdate into a Y-m-d string, returning '' if missing
 * or unparseable. Vipps returns ISO date "YYYY-MM-DD" per OIDC.
 */
function trustaiVippsParseBirthDate(?string $raw): string
{
    if ($raw === null || $raw === '') return '';
    $raw = trim($raw);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) !== 1) return '';
    $d = DateTime::createFromFormat('Y-m-d', $raw);
    if (!$d) return '';
    $errors = DateTime::getLastErrors();
    if (!empty($errors['warning_count']) || !empty($errors['error_count'])) return '';
    return $d->format('Y-m-d');
}

function trustaiVippsAgeFromBirthDate(string $birthDate, ?DateTimeInterface $now = null): ?int
{
    if ($birthDate === '') return null;
    try {
        $bd = new DateTimeImmutable($birthDate);
    } catch (Throwable $e) {
        return null;
    }
    $today = $now instanceof DateTimeInterface
        ? DateTimeImmutable::createFromInterface($now)
        : new DateTimeImmutable('today');
    return (int)$today->diff($bd)->y;
}

/**
 * Log a CSRF / state mismatch with non-secret context to help debugging
 * without leaking the actual CSRF or session values.
 */
function trustaiVippsLogStateIssue(string $reason, array $ctx = []): void
{
    $safe = [
        'sid_len' => strlen((string)session_id()),
        'has_csrf' => isset($_SESSION['vipps_csrf']),
        'has_pkce' => isset($_SESSION['vipps_pkce_verifier']),
        'has_intent' => isset($_SESSION['vipps_intent']),
        'cookie_present' => isset($_COOKIE[session_name()]),
        'remote' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 24),
    ] + $ctx;
    error_log('Vipps state issue [' . $reason . '] ' . json_encode($safe));
}

/**
 * Always-on structured debug log for Vipps endpoints. Only logs
 * non-secret fields (no codes, tokens, secrets, verifiers).
 */
function trustaiVippsDebugLog(string $event, array $ctx = []): void
{
    error_log('Vipps[' . $event . '] ' . json_encode($ctx, JSON_UNESCAPED_SLASHES));
}
