<?php
$jsonStr = file_get_contents(__DIR__ . '/dbf_data.json');
$dbfRows = json_decode($jsonStr, true);

echo "Buscando montos de 1092.95 o combinaciones en el DBF para 2023...\n";
$target = 1092.95;
$found = false;

foreach ($dbfRows as $row) {
    if (abs($row['debito'] - $target) < 0.1 || abs($row['credito'] - $target) < 0.1) {
        print_r($row);
        $found = true;
    }
}

if (!$found) {
    echo "No se encontró un monto exacto. Buscando registros de la cuenta 11050101 en el DBF...\n";
    $sum = 0;
    foreach ($dbfRows as $row) {
        if ($row['acct'] == '11050101' && date('Y', strtotime($row['fecha'])) == '2023') {
            $sum += ($row['debito'] - $row['credito']);
        }
    }
    echo "Suma total en DBF para 11050101 en 2023: " . number_format($sum, 2) . "\n";
}
