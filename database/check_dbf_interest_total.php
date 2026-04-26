<?php
$jsonStr = file_get_contents(__DIR__ . '/dbf_data.json');
$dbfRows = json_decode($jsonStr, true);

$sum = 0;
foreach ($dbfRows as $row) {
    if (strpos($row['detalle'], 'Intereses') !== false && date('Y', strtotime($row['fecha'])) == '2023') {
        // En el sistema viejo: debito - credito
        $sum += ($row['debito'] - $row['credito']);
    }
}
echo "Suma de intereses en DBF (2023): " . $sum . "\n";
