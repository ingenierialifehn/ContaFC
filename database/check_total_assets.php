<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SELECT SUM(a.debito - a.credito) 
                   FROM asientos a 
                   JOIN puc_cuentas p ON a.cuenta_id = p.id 
                   WHERE p.codigo LIKE '1%' AND YEAR(a.fecha) <= 2023");
echo "Total MySQL Assets (<= 2023): " . $stmt->fetchColumn() . "\n";
