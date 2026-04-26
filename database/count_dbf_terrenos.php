<?php
$jsonStr = file_get_contents(__DIR__ . '/dbf_data.json');
$dbfRows = json_decode($jsonStr, true);

$cnt = 0;
foreach ($dbfRows as $row) {
    if ($row['acct'] == '12010106' && ($row['fecha'] && substr($row['fecha'], 0, 4) <= '2023')) {
        $cnt++;
    }
}
echo "DBF Count 12010106: $cnt\n";
