<?php
$res = file_put_contents(__DIR__ . '/api_debug.log', "Test at " . date('H:i:s') . "\n", FILE_APPEND);
echo "Written: " . ($res !== false ? 'YES' : 'NO') . " to " . __DIR__ . "/api_debug.log";
