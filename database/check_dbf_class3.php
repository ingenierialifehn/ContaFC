<?php
$jsonStr = file_get_contents(__DIR__ . '/dbf_data.json');
$dbfRows = json_decode($jsonStr, true);

echo "Buscando Clase 3 en el DBF...\n";
foreach ($dbfRows as $row) {
    if (substr($row['acct'], 0, 1) == '3') {
        print_r($row);
    }
}
