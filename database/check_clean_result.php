<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SELECT SUM(a.credito - a.debito) 
                   FROM asientos a 
                   JOIN puc_cuentas p ON a.cuenta_id = p.id 
                   WHERE (p.codigo LIKE '4%' OR p.codigo LIKE '5%' OR p.codigo LIKE '6%') AND YEAR(a.fecha) = 2023 AND a.conteo IS NOT NULL");
echo "Period result 2023 (ONLY CONTEO): " . $stmt->fetchColumn() . "\n";
