<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "VERIFICACIÓN DE SALDOS CON LÓGICA DE DEDUPLICACIÓN VIRTUAL\n\n";

$accounts = ['11020104' => 'Bco Occidente', '12010106' => 'Terrenos', '11050101' => 'CxC Clientes'];

foreach ($accounts as $code => $label) {
    $stmt = $db->prepare("
        SELECT 
            COALESCE(SUM(
                CASE 
                    WHEN c.id IS NOT NULL 
                         AND NOT (a.descripcion LIKE '%SALDO SEG%N LIBROS%' AND c.id >= 10000)
                    THEN (a.debito - a.credito)
                    ELSE 0 
                END
            ), 0) AS saldo_neto
        FROM puc_cuentas p
        LEFT JOIN asientos a ON a.cuenta_id = p.id
        LEFT JOIN comprobantes c ON c.id = a.comprobante_id
        WHERE p.codigo = :code AND p.empresa_id = 1
        AND YEAR(c.fecha) <= 2023
        AND c.estado = 'registrado'
    ");
    $stmt->execute([':code' => $code]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "$label ($code): Saldo = " . number_format($res['saldo_neto'], 2) . "\n";
}
?>
