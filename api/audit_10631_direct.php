<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "COMPROBANTE 10631 AND OTHERS\n";
$stmt = $db->query("
    SELECT id, fecha FROM comprobantes WHERE id >= 10600 AND empresa_id = 1
");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\nASIENTOS IN 10631:\n";
$stmt = $db->query("
    SELECT a.id, p.codigo, a.debito, a.credito, a.descripcion 
    FROM asientos a 
    JOIN puc_cuentas p ON a.cuenta_id = p.id
    WHERE a.comprobante_id = 10631
");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
