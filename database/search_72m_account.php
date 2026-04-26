<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SELECT p.codigo, p.nombre, SUM(a.debito - a.credito) as saldo 
                   FROM asientos a 
                   JOIN puc_cuentas p ON a.cuenta_id = p.id 
                   GROUP BY p.codigo, p.nombre 
                   HAVING ABS(saldo - 72351066.74) < 1000");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
