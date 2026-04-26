<?php
$jsonStr = file_get_contents(__DIR__ . '/dbf_data.json');
$dbfRows = json_decode($jsonStr, true);

$balance = 0;
$cnt = 0;
foreach ($dbfRows as $row) {
    if ($row['acct'] == '11050101' && ($row['fecha'] && substr($row['fecha'], 0, 4) <= '2023')) {
        $balance += $row['debito'] - $row['credito'];
        $cnt++;
    }
}
echo "DBF Balance 11050101 (<= 2023): $balance ($cnt records)\n";
