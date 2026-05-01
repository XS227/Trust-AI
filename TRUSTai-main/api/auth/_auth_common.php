<?php
declare(strict_types=1);

require_once __DIR__ . '/../_auth.php';

function trustaiGetEnv(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return $value;
}

function trustaiRoleRedirect(array $user): string
{
    $role = (string)($user['role'] ?? '');
    return match ($role) {
        'super_admin' => '/super-admin.html',
        'store_admin' => '/store-admin.html',
        default => '/ambassador-dashboard.html',
    };
}

function trustaiCanLogin(array $user): bool
{
    $status = strtolower((string)($user['status'] ?? 'active'));
    return !in_array($status, ['pending', 'paused', 'inactive', 'rejected'], true);
}

function trustaiBlockedStatusMessage(array $user): string
{
    $status = strtolower((string)($user['status'] ?? 'active'));
    return match ($status) {
        'pending' => 'Kontoen din er under vurdering.',
        'paused' => 'Kontoen din er midlertidig pauset. Kontakt support.',
        'inactive', 'rejected' => 'Kontoen din er ikke aktiv. Kontakt support.',
        default => 'Kontoen din er ikke aktiv.',
    };
}

function trustaiStartSessionForUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['trustai_user_id'] = (int)$user['id'];
    $_SESSION['trustai_user_email'] = (string)$user['email'];
    $_SESSION['trustai_user'] = $user;
}
