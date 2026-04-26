<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$stmt = $db->query("SELECT id, codigo, nombre FROM puc_cuentas WHERE nombre LIKE '%Clientes%' OR nombre LIKE '%Cuentas por cobrar%'");
$cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($cuentas, JSON_PRETTY_PRINT);
