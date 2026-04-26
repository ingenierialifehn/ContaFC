<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$row = $db->query("SELECT id, codigo, nombre FROM puc_cuentas WHERE codigo IN ('11050101', '110301', '11020101')")->fetchAll(PDO::FETCH_ASSOC);
print_r($row);
