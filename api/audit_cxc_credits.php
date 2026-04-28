<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "ALL CREDITS FOR CXC CLIENTES (11050101)\n";
$stmt = $db->query("
    SELECT a.id, a.credito, c.id as comp_id, c.fecha, a.descripcion
    FROM asientos a
    JOIN puc_cuentas p ON a.cuenta_id = p.id
    JOIN comprobantes c ON a.comprobante_id = c.id
    WHERE p.codigo = '11050101' AND a.credito > 0 AND p.empresa_id = 1
");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
