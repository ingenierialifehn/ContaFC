<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT codigo, nombre, LENGTH(codigo) as len FROM puc_cuentas LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($res);
echo "</pre>";
