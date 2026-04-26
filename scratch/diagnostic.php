<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();

echo "--- PUC CUENTAS CHECK ---\n";
$stmt = $db->query("SELECT id, codigo, nombre FROM puc_cuentas WHERE codigo IN ('110301', '11050101', '11020101')");
$target_ids = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']} | Codigo: {$row['codigo']} | Nombre: {$row['nombre']}\n";
    $target_ids[] = (int)$row['id'];
}

echo "\n--- ASIENTOS INDEXES ---\n";
$stmt = $db->query("SHOW INDEX FROM asientos");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Table: {$row['Table']} | Key_name: {$row['Key_name']} | Column_name: {$row['Column_name']} | Non_unique: {$row['Non_unique']}\n";
}

echo "\n--- ASIENTOS SAMPLE ---\n";
$stmt = $db->query("SELECT id, comprobante_id, linea, fecha, cuenta_id, conteo FROM asientos LIMIT 2");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
