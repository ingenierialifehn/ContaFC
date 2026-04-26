<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SELECT id, codigo, nombre FROM puc_cuentas WHERE codigo LIKE '1%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
