<?php
require_once __DIR__ . '/../bootstrap.php';
$service = new ContaFC\Services\OfficialBookService();
$data = $service->getComparativeBalance(1, 2025);
$lengths = [];
foreach ($data as $r) {
    $len = strlen((string)$r['codigo']);
    $lengths[$len] = ($lengths[$len] ?? 0) + 1;
}
print_r($lengths);
