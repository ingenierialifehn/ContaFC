<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT p.codigo, p.nombre, COUNT(*) as count 
                  FROM asientos a 
                  JOIN puc_cuentas p ON a.cuenta_id = p.id 
                  WHERE LENGTH(p.codigo) < 6 
                  GROUP BY p.codigo, p.nombre")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
