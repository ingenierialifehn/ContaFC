<?php
require_once __DIR__ . '/../bootstrap.php';
$service = new ContaFC\Services\OfficialBookService();
$data = $service->getComparativeBalance(1, 2023);
foreach ($data as $r) {
    if ($r['nivel'] == 1) {
        echo "Account: {$r['codigo']} | Name: {$r['nombre']} | Bal: {$r['saldo_actual']}\n";
    }
}
echo "Total count: " . count($data) . "\n";
