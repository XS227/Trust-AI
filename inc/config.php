<?php

// Load environment-specific overrides first (DB credentials, OAuth secrets, etc.).
// Anything `define()`'d in config.local.php wins because of the `if (!defined())`
// guards below.
if (is_readable(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

// Database config (ProISP)
// DB_PASS has NO real default on purpose (2026-07-12) — it used to fall
// back to a hardcoded password, which meant that value was sitting in git
// history in plaintext, AND meant a misconfigured/unreadable
// config.local.php failed silently by connecting with a stale password
// instead of failing loudly (see docs/DB_INCIDENT_2026-07-11.md — that
// exact failure mode caused a real outage). inc/db.php's
// $trustAiDbValueIsPlaceholder() check already treats an empty DB_PASS as
// "not configured" and throws db_not_configured, so leaving it empty here
// is what makes that fail fast instead of connecting with the wrong thing.
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'trustai');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

// Optional: debug mode (sett til false i prod)
if (!defined('APP_DEBUG')) define('APP_DEBUG', true);

// Environment identifier (local | dev | development | test | production)
if (!defined('APP_ENV')) define('APP_ENV', 'production');

// Demo/test mode. Enable by setting DEMO_MODE=true in env or config.local.php.
// Automatically true when APP_ENV is local/dev/development/test.
if (!defined('DEMO_MODE')) define('DEMO_MODE', false);

// Vipps Login (OIDC). Set in inc/config.local.php or as environment variables.
if (!defined('VIPPS_ENV')) define('VIPPS_ENV', 'test');
if (!defined('VIPPS_CLIENT_ID')) define('VIPPS_CLIENT_ID', '');
if (!defined('VIPPS_CLIENT_SECRET')) define('VIPPS_CLIENT_SECRET', '');
if (!defined('VIPPS_SUBSCRIPTION_KEY')) define('VIPPS_SUBSCRIPTION_KEY', '');
if (!defined('VIPPS_MSN')) define('VIPPS_MSN', '');
if (!defined('VIPPS_REDIRECT_URI')) define('VIPPS_REDIRECT_URI', 'https://trustai.no/api/auth/vipps/callback.php');
