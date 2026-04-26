<?php
$f = @fopen(__DIR__ . '/../database/Resumen VF asientos.DBF', 'rb');
if ($f) {
    echo "Success\n";
    fclose($f);
} else {
    echo "Fail: " . error_get_last()['message'] . "\n";
}
