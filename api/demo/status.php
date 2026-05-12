<?php
declare(strict_types=1);

require_once __DIR__ . '/_demo_helper.php';

jsonResponse(200, ['demo_mode' => isDemoMode()]);
