<?php
require_once __DIR__ . '/bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();

$empId = 1; // From image
$year = 2023;

// Summary of 2023 entries for account 11050101
$sql = "
    SELECT 
        YEAR(COALESCE(a.fecha, c.fecha)) as anio,
        COUNT(*) as total_filas,
        SUM(a.debito) as total_debito,
        SUM(a.credito) as total_credito,
        SUM(a.debito - a.credito) as total_neto
    FROM asientos a
    JOIN comprobantes c ON a.comprobante_id = c.id
    JOIN puc_cuentas p ON a.cuenta_id = p.id
    WHERE p.codigo = '11050101' AND a.empresa_id = :e 
      AND c.estado = 'registrado'
    GROUP BY YEAR(COALESCE(a.fecha, c.fecha))
    ORDER BY anio
";

$st = $db->prepare($sql);
$st->execute(['e' => $empId]);
$res = $st->fetchAll(PDO::FETCH_ASSOC);

echo "Resumen por año para cuenta 11050101:\n";
print_r($res);

// Check negative credits
$sqlNeg = "
    SELECT COUNT(*) as neg_credits, SUM(credito) as sum_neg_credits
    FROM asientos a
    JOIN comprobantes c ON a.comprobante_id = c.id
    WHERE cuenta_id = (SELECT id FROM puc_cuentas WHERE codigo='11050101' AND empresa_id=:e LIMIT 1)
      AND a.credito < 0 AND YEAR(COALESCE(a.fecha, c.fecha)) = 2023
";
$stNeg = $db->prepare($sqlNeg);
$stNeg->execute(['e' => $empId]);
echo "\nCréditos negativos en 2023:\n";
print_r($stNeg->fetch(PDO::FETCH_ASSOC));
