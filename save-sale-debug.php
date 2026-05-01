<?php
header("Content-Type: text/plain");

$raw = file_get_contents("php://input");

file_put_contents(
    __DIR__ . "/sales/ambassador-debug-2.log",
    "=== NEW HIT ===\n" .
    "RAW:\n" . $raw . "\n\n",
    FILE_APPEND
);

echo "ok";
?>
