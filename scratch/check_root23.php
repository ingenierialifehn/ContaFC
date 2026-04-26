<?php
require_once __DIR__ . '/../bootstrap.php';
$service = new ContaFC\Services\OfficialBookService();
$data = $service->getComparativeBalance(1, 2023);
foreach ($data as $r) {
    if ($r['codigo'] == '2' || $r['codigo'] == '3') {
        echo "Root {$r['codigo']}: {$r['saldo_actual']}\n";
    }
}
