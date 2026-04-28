<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();
$eid = 1; // Assuming Empresa 1 based on screenshot
$year = 2023;

echo "DEBUG FOR CXC CLIENTES (11050101) IN 2023\n";
$stmt = $db->prepare("
    SELECT a.debito, a.credito, a.fecha, a.descripcion, c.estado, c.id as comp_id
    FROM asientos a
    JOIN puc_cuentas p ON a.cuenta_id = p.id
    INNER JOIN comprobantes c ON a.comprobante_id = c.id
    WHERE p.codigo = '11050101' AND p.empresa_id = :eid
    AND c.estado = 'registrado'
    AND YEAR(c.fecha) <= :year
    ORDER BY c.fecha DESC
    LIMIT 20
");
$stmt->execute([':eid' => $eid, ':year' => $year]);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\nTOTAL SUM:\n";
$stmt = $db->prepare("
    SELECT SUM(a.debito - a.credito) as total
    FROM asientos a
    JOIN puc_cuentas p ON a.cuenta_id = p.id
    INNER JOIN comprobantes c ON a.comprobante_id = c.id
    WHERE p.codigo = '11050101' AND p.empresa_id = :eid
    AND c.estado = 'registrado'
    AND YEAR(c.fecha) <= :year
");
$stmt->execute([':eid' => $eid, ':year' => $year]);
print_r($stmt->fetch(PDO::FETCH_ASSOC));
?>
