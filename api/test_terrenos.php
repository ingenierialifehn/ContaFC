<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

$sql = "SELECT
            p.codigo,
            COALESCE(SUM(
                CASE
                    WHEN c.id IS NOT NULL THEN (a.debito - a.credito)
                    ELSE 0
                END
            ), 0) AS saldo_neto
        FROM puc_cuentas p
        LEFT JOIN asientos a
            ON a.cuenta_id = p.id
           AND a.empresa_id = p.empresa_id
        LEFT JOIN comprobantes c
            ON c.id = a.comprobante_id
           AND c.empresa_id = p.empresa_id
           AND c.estado = 'registrado'
           AND YEAR(c.fecha) <= 2023
        WHERE p.codigo = '12010106' AND p.empresa_id = 1
        GROUP BY p.codigo";

$stmt = $db->query($sql);
print_r($stmt->fetch(PDO::FETCH_ASSOC));

echo "\nBREAKDOWN FOR TERRENOS:\n";
$stmt = $db->query("
    SELECT a.id, a.debito, a.credito, a.descripcion, c.fecha
    FROM asientos a
    JOIN puc_cuentas p ON a.cuenta_id = p.id
    LEFT JOIN comprobantes c ON a.comprobante_id = c.id
    WHERE p.codigo = '12010106' AND p.empresa_id = 1
    AND c.estado = 'registrado'
    AND YEAR(c.fecha) <= 2023
");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
