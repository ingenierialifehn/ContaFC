<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();
$eid = 1;

echo "DEBUG ALL ASIENTOS FOR 11050101\n";
$stmt = $db->prepare("
    SELECT SUM(a.debito - a.credito) as total, COUNT(*) as qty
    FROM asientos a
    JOIN puc_cuentas p ON a.cuenta_id = p.id
    WHERE p.codigo = '11050101' AND p.empresa_id = :eid
");
$stmt->execute([':eid' => $eid]);
print_r($stmt->fetch(PDO::FETCH_ASSOC));

echo "\nSUM WITH COMPROBANTE JOIN (STATE REGISTRADO):\n";
$stmt = $db->prepare("
    SELECT SUM(a.debito - a.credito) as total, COUNT(*) as qty
    FROM asientos a
    JOIN puc_cuentas p ON a.cuenta_id = p.id
    LEFT JOIN comprobantes c ON a.comprobante_id = c.id
    WHERE p.codigo = '11050101' AND p.empresa_id = :eid
    AND c.estado = 'registrado'
");
$stmt->execute([':eid' => $eid]);
print_r($stmt->fetch(PDO::FETCH_ASSOC));

echo "\nSUM WITH COMPROBANTE JOIN (ANY STATE):\n";
$stmt = $db->prepare("
    SELECT SUM(a.debito - a.credito) as total, c.estado, COUNT(*) as qty
    FROM asientos a
    JOIN puc_cuentas p ON a.cuenta_id = p.id
    LEFT JOIN comprobantes c ON a.comprobante_id = c.id
    WHERE p.codigo = '11050101' AND p.empresa_id = :eid
    GROUP BY c.estado
");
$stmt->execute([':eid' => $eid]);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
