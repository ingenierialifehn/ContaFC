<?php
require_once __DIR__ . '/../bootstrap.php';
$service = new ContaFC\Services\OfficialBookService();
$data = $service->getComparativeBalance(1, 2025);
echo "Data count 2025: " . count($data) . "\n";
