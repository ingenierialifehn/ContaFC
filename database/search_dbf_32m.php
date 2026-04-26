<?php
$jsonStr = file_get_contents(__DIR__ . '/dbf_data.json');
$dbfRows = json_decode($jsonStr, true);

foreach ($dbfRows as $row) {
    if (abs($row['debito'] - 32885333.33) < 1) {
        print_r($row);
    }
}
