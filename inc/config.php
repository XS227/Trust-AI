<?php

// Load environment-specific overrides first (DB credentials, OAuth secrets, etc.).
// Anything `define()`'d in config.local.php wins because of the `if (!defined())`
// guards below.
if (is_readable(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

// Database config (ProISP)
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'trustai');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', 'NyttPassord123!');

// Optional: debug mode (sett til false i prod)
if (!defined('APP_DEBUG')) define('APP_DEBUG', true);

// Vipps Login (OIDC). Set in inc/config.local.php or as environment variables.
if (!defined('VIPPS_ENV')) define('VIPPS_ENV', 'test');
if (!defined('VIPPS_CLIENT_ID')) define('VIPPS_CLIENT_ID', '');
if (!defined('VIPPS_CLIENT_SECRET')) define('VIPPS_CLIENT_SECRET', '');
if (!defined('VIPPS_SUBSCRIPTION_KEY')) define('VIPPS_SUBSCRIPTION_KEY', '');
if (!defined('VIPPS_MSN')) define('VIPPS_MSN', '');
if (!defined('VIPPS_REDIRECT_URI')) define('VIPPS_REDIRECT_URI', 'https://trustai.no/api/auth/vipps/callback.php');
