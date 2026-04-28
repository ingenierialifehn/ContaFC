<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "DUPLICATES AUDIT FOR 11020104 (BCO OCCIDENTE) IN EMPRESA 1\n";
$stmt = $db->prepare("
    SELECT id, debito, credito, fecha, descripcion, created_at, comprobante_id
    FROM asientos
    WHERE cuenta_id = 85 -- ID for Bco Occidente in Empresa 1
");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo json_encode($row) . "\n";
}
?>
