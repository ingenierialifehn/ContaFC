<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();
$eid = 2; // NOW USING CORRECT ID

echo "DEBUG FOR CXC CLIENTES (11050101) IN EMPRESA 2\n";
$stmt = $db->prepare("
    SELECT SUM(a.debito - a.credito) as total, COUNT(*) as qty
    FROM asientos a
    JOIN puc_cuentas p ON a.cuenta_id = p.id
    WHERE p.codigo = '11050101' AND p.empresa_id = :eid
");
$stmt->execute([':eid' => $eid]);
print_r($stmt->fetch(PDO::FETCH_ASSOC));

echo "\nFIND DUPLICATES FOR BCO OCCIDENTE (11020104) IN EMPRESA 2\n";
$stmt = $db->prepare("
    SELECT a.id, a.debito, a.credito, a.fecha, a.descripcion, a.created_at, a.comprobante_id
    FROM asientos a
    JOIN puc_cuentas p ON a.cuenta_id = p.id
    WHERE p.codigo = '11020104' AND p.empresa_id = :eid
");
$stmt->execute([':eid' => $eid]);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
