<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Services\OfficialBookService;

$service = new OfficialBookService();
$eid = 1;
$year = 2026;

echo "DEBUGGING BALANCE FOR YEAR: $year\n";
$data = $service->getComparativeBalance($eid, $year);

echo "Count of records returned: " . count($data) . "\n";
if (count($data) > 0) {
    echo "First 5 records:\n";
    print_r(array_slice($data, 0, 5));
} else {
    echo "NO DATA RETURNED.\n";
}
