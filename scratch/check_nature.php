<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT codigo, nombre, naturaleza FROM puc_cuentas WHERE codigo = '21010102'")->fetch(PDO::FETCH_ASSOC);
print_r($res);
