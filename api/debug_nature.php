<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();
$eid = 1;

echo "DEBUG NATURE AND BALANCE FOR 11050101\n";
$stmt = $db->prepare("
    SELECT codigo, nombre, naturaleza, nivel, tipo_cuenta
    FROM puc_cuentas
    WHERE codigo = '11050101' AND empresa_id = :eid
");
$stmt->execute([':eid' => $eid]);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $db->prepare("
    SELECT SUM(debito) as deb, SUM(credito) as cre, COUNT(*) as qty
    FROM asientos a
    JOIN puc_cuentas p ON a.cuenta_id = p.id
    INNER JOIN comprobantes c ON a.comprobante_id = c.id
    WHERE p.codigo = '11050101' AND p.empresa_id = :eid
    AND c.estado = 'registrado'
    AND YEAR(c.fecha) <= 2023
");
$stmt->execute([':eid' => $eid]);
print_r($stmt->fetch(PDO::FETCH_ASSOC));
?>
