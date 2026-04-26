<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Database;

$jsonStr = file_get_contents(__DIR__ . '/dbf_data.json');
$dbfRows = json_decode($jsonStr, true);

foreach ($dbfRows as $row) {
    if (stripos($row['desc'] ?? '', 'intereses') !== false || stripos($row['detalle'] ?? '', 'intereses') !== false) {
        if ($row['credito'] < 0 || $row['debito'] < 0) {
            echo "Conteo: {$row['conteo']}, Fecha: {$row['fecha']}, Deb: {$row['debito']}, Cre: {$row['credito']}, SaldoAnt: {$row['saldant']}, Saldo: {$row['saldo']}, Desc: {$row['desc']}, Det: {$row['detalle']}\n";
        }
    }
}
