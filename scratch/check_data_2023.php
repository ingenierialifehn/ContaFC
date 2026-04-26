<?php
require_once __DIR__ . '/../bootstrap.php';
$service = new ContaFC\Services\OfficialBookService();
$data = $service->getComparativeBalance(1, 2023);
echo "Data count: " . count($data) . "\n";
if (count($data) > 0) {
    print_r(array_slice($data, 0, 5));
} else {
    echo "NO DATA FOUND FOR 2023\n";
}
