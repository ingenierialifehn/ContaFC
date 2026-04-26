<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SELECT id, codigo, nombre FROM puc_cuentas WHERE codigo = '110301'");
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
