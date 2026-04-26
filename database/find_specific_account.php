<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SELECT id, codigo, nombre FROM puc_cuentas WHERE codigo = '11050121'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
