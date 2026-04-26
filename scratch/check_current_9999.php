<?php
require __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();
$sql = "SELECT c.codigo, COUNT(*) as total 
        FROM asientos a 
        JOIN puc_cuentas c ON a.cuenta_id = c.id 
        WHERE a.comprobante_id = 9999 
        GROUP BY c.codigo 
        ORDER BY total DESC LIMIT 10";
$res = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
