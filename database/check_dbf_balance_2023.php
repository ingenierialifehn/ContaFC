<?php
$jsonStr = file_get_contents(__DIR__ . '/dbf_data.json');
$dbfRows = json_decode($jsonStr, true);

$sum = 0;
foreach ($dbfRows as $row) {
    if (date('Y', strtotime($row['fecha'])) == '2023') {
        $sum += ($row['debito'] - $row['credito']);
    }
}
echo "Suma total de débitos y créditos en DBF para 2023: " . number_format($sum, 2) . "\n";
