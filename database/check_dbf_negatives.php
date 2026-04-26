<?php
$jsonStr = file_get_contents(__DIR__ . '/dbf_data.json');
$dbfRows = json_decode($jsonStr, true);

$sumNeg = 0;
foreach ($dbfRows as $row) {
    if (strpos($row['detalle'], 'Intereses') !== false && $row['credito'] < 0) {
        $sumNeg += abs($row['credito']);
    }
}
echo "Suma de créditos negativos en Intereses del DBF: " . $sumNeg . "\n";
echo "Efecto en el balance (2 * Sum): " . (2 * $sumNeg) . "\n";
