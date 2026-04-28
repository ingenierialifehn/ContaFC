<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "SEARCHING FOR 17830.00 IN 2023\n";
$stmt = $db->query("
    SELECT a.id, a.debito, a.credito, a.descripcion, c.id as comp_id, c.fecha, a.created_at
    FROM asientos a
    JOIN puc_cuentas p ON a.cuenta_id = p.id
    JOIN comprobantes c ON a.comprobante_id = c.id
    WHERE p.codigo = '11020104' AND p.empresa_id = 1
    AND (a.debito = 17830.00 OR a.credito = 17830.00)
    AND YEAR(c.fecha) = 2023
");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
