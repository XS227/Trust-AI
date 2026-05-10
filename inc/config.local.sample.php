<?php

// Copy to inc/config.local.php and fill in real values. This file is gitignored
// (see .gitignore). inc/config.php auto-loads config.local.php if it exists.
//
// All defines use the `if (!defined())` guard, so config.local.php overrides
// what inc/config.php declares as defaults.

// --- Database (optional override — defaults to localhost in inc/config.php) ---
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'trustai');
// define('DB_USER', 'root');
// define('DB_PASS', 'change_me');

// --- Debug ---
if (!defined('APP_DEBUG')) define('APP_DEBUG', false);

// --- Vipps Login (OIDC). Values from the Vipps developer portal. ---
if (!defined('VIPPS_ENV'))              define('VIPPS_ENV', 'test'); // 'test' or 'prod'
if (!defined('VIPPS_CLIENT_ID'))        define('VIPPS_CLIENT_ID', '');
if (!defined('VIPPS_CLIENT_SECRET'))    define('VIPPS_CLIENT_SECRET', '');
if (!defined('VIPPS_SUBSCRIPTION_KEY')) define('VIPPS_SUBSCRIPTION_KEY', '');
if (!defined('VIPPS_MSN'))              define('VIPPS_MSN', '');
if (!defined('VIPPS_REDIRECT_URI'))     define('VIPPS_REDIRECT_URI', 'https://trustai.no/api/auth/vipps/callback.php');

// --- Google Login (optional) ---
// putenv('GOOGLE_CLIENT_ID=...');
// putenv('GOOGLE_CLIENT_SECRET=...');
