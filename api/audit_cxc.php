<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "AUDIT FOR CXC CLIENTES (11050101)\n";
$stmt = $db->query("
    SELECT a.id, a.debito, a.credito, a.descripcion, c.id as comp_id, c.fecha
    FROM asientos a
    JOIN puc_cuentas p ON a.cuenta_id = p.id
    JOIN comprobantes c ON a.comprobante_id = c.id
    WHERE p.codigo = '11050101' AND p.empresa_id = 1
    ORDER BY c.fecha ASC
");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
