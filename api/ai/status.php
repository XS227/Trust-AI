<?php
declare(strict_types=1);
require_once __DIR__ . '/../../inc/bootstrap.php';
require_once __DIR__ . '/_helper.php';

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ok'         => true,
    'ai_enabled' => trustaiAiIsEnabled(),
]);
