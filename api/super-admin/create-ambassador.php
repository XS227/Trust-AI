<?php
declare(strict_types=1);

require_once __DIR__ . '/../_auth.php';

$user = requireLogin();
if (!isSuperAdmin($user)) {
    jsonResponse(403, ['ok' => false, 'error' => 'forbidden_role']);
}

require __DIR__ . '/../store-admin/create-ambassador.php';
