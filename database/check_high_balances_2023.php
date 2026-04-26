<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SELECT p.codigo, p.nombre, SUM(a.debito - a.credito) as saldo 
                   FROM asientos a 
                   JOIN puc_cuentas p ON a.cuenta_id = p.id 
                   JOIN comprobantes c ON a.comprobante_id = c.id 
                   WHERE YEAR(c.fecha) <= 2023 AND c.estado = 'registrado' 
                   GROUP BY p.codigo, p.nombre 
                   HAVING ABS(saldo) > 1000000 
                   ORDER BY ABS(saldo) DESC");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
