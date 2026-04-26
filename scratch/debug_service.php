<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Services\OfficialBookService;

$service = new OfficialBookService();
$eid = 1;
$year = 2025;

echo "DEBUGGING BALANCE FOR YEAR: $year\n";
$data = $service->getComparativeBalance($eid, $year);

echo "Count of records returned: " . count($data) . "\n";
if (count($data) > 0) {
    echo "First 5 records:\n";
    print_r(array_slice($data, 0, 5));
} else {
    echo "NO DATA RETURNED.\n";
}

$year2 = 2023;
echo "\nDEBUGGING BALANCE FOR YEAR: $year2\n";
$data2 = $service->getComparativeBalance($eid, $year2);
echo "Count of records returned: " . count($data2) . "\n";
if (count($data2) > 0) {
    echo "First 5 records:\n";
    print_r(array_slice($data2, 0, 5));
} else {
    echo "NO DATA RETURNED.\n";
}
