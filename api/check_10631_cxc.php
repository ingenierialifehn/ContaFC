<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "IS CXC CLIENTES IN 10631?\n";
$stmt = $db->query("
    SELECT a.debito, a.credito, a.descripcion
    FROM asientos a
    JOIN puc_cuentas p ON a.cuenta_id = p.id
    WHERE a.comprobante_id = 10631 AND p.codigo = '11050101'
");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
