<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();

$stmtCheck = $db->query("SELECT SUM(debito - credito) as saldo_neto FROM asientos a 
                         JOIN comprobantes c ON a.comprobante_id = c.id 
                         WHERE a.cuenta_id = (SELECT id FROM puc_cuentas WHERE codigo = '11050121' LIMIT 1) 
                         AND YEAR(c.fecha) <= 2023");
$saldo2023 = $stmtCheck->fetchColumn();
echo "Saldo neto 11050121 para 2023: $saldo2023\n";
