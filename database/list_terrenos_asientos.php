<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SELECT id, conteo, debito, credito, descripcion, fecha FROM asientos WHERE cuenta_id = (SELECT id FROM puc_cuentas WHERE codigo = '12010106')");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
