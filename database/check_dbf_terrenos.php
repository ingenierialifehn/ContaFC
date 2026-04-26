<?php
$jsonStr = file_get_contents(__DIR__ . '/dbf_data.json');
$dbfRows = json_decode($jsonStr, true);

$deb = 0; $cre = 0;
foreach ($dbfRows as $row) {
    if ($row['acct'] == '12010106' && ($row['fecha'] && substr($row['fecha'], 0, 4) <= '2023')) {
        $deb += $row['debito'];
        $cre += $row['credito'];
    }
}
echo "DBF 12010106: Deb=$deb, Cre=$cre, Bal=" . ($deb - $cre) . "\n";
