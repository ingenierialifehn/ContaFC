<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SELECT p.codigo, p.nombre, SUM(a.debito) as deb, SUM(a.credito) as cre, COUNT(*) as cnt 
                   FROM asientos a 
                   JOIN puc_cuentas p ON a.cuenta_id = p.id 
                   GROUP BY p.codigo, p.nombre 
                   ORDER BY SUM(ABS(a.debito) + ABS(a.credito)) DESC 
                   LIMIT 20");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
