<?php
require __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();
$res = $db->query("SELECT id, codigo, nombre FROM puc_cuentas WHERE codigo LIKE '1102%' OR codigo LIKE '1103%' OR codigo LIKE '1105%'")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
