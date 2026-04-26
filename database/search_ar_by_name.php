<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SELECT id, codigo, nombre FROM puc_cuentas WHERE nombre LIKE '%Cuentas por cobrar%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
