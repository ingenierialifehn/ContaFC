<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT codigo, nombre, naturaleza FROM puc_cuentas WHERE SUBSTR(codigo,1,1) = '2' AND naturaleza = 'D'")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
