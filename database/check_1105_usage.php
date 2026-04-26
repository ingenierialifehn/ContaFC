<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SELECT DISTINCT p.codigo, p.nombre 
                   FROM asientos a 
                   JOIN puc_cuentas p ON a.cuenta_id = p.id 
                   WHERE p.codigo LIKE '1105%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
