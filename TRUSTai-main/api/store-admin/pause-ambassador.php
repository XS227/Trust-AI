<?php
declare(strict_types=1);
require_once __DIR__ . '/../_auth.php';
$body = readJsonBody();
$body['status'] = 'paused';
$GLOBALS['__body'] = $body;
require __DIR__ . '/_ambassador_status_runner.php';
