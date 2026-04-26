<?php
require_once __DIR__ . '/../bootstrap.php';
$service = new ContaFC\Services\OfficialBookService();
$year = 2023;
$empresa_id = 1;
$data = $service->getComparativeBalance($empresa_id, $year);
echo "Data count for 2023: " . count($data) . "\n";
if (count($data) > 0) {
    echo "First row:\n";
    print_r($data[0]);
}
