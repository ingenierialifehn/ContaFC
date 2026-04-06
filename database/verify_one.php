<?php
require_once __DIR__ . '/../bootstrap.php';
$codigo = '12010106';
$stmt = ContaFC\Core\Database::getInstance()->getPdo()->prepare("SELECT nombre FROM puc_cuentas WHERE codigo = ? AND empresa_id = 1");
$stmt->execute([$codigo]);
echo "Nombre para $codigo: " . $stmt->fetchColumn() . PHP_EOL;
