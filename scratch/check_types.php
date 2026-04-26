<?php
require_once __DIR__ . '/../bootstrap.php';
$service = new ContaFC\Services\OfficialBookService();
$data = $service->getComparativeBalance(1, 2023);
$types = [];
foreach ($data as $r) {
    $types[$r['tipo_cuenta']] = ($types[$r['tipo_cuenta']] ?? 0) + 1;
}
print_r($types);
