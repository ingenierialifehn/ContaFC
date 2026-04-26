<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT SUBSTR(codigo,1,1) as d1, naturaleza, COUNT(*) FROM puc_cuentas GROUP BY d1, naturaleza")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
