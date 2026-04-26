<?php
$jsonStr = file_get_contents(__DIR__ . '/dbf_data.json');
$dbfRows = json_decode($jsonStr, true);

$sum = 0;
foreach ($dbfRows as $row) {
    if (strpos($row['detalle'], 'Intereses') !== false && date('Y', strtotime($row['fecha'])) == '2023') {
        $sum += abs($row['credito'] < 0 ? $row['credito'] : 0);
    }
}
echo "Suma de intereses NEGATIVOS en DBF (2023): " . $sum . "\n";
