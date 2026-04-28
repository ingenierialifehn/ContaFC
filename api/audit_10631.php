<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "VOUCHER 10631 CONTENT\n";
$stmt = $db->query("
    SELECT a.id, p.codigo, p.nombre, a.debito, a.credito, a.descripcion
    FROM asientos a
    JOIN puc_cuentas p ON a.cuenta_id = p.id
    WHERE a.comprobante_id = 10631
");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
