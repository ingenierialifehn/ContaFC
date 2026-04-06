<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
$filePath = __DIR__ . '/cuentas.txt';
$lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$headerToCode = [
    'ACTIVOS' => '1',
    'CIRCULANTE' => '11',
    'FIJO' => '12',
    'DIFERIDO' => '13',
    'PASIVO' => '2',
    'PLANILLAS POR PAGAR' => '22',
    'OTRAS CUENTAS POR PAGAR' => '23',
    'PRESTAMOS POR PAGAR' => '24',
    'RETENCIONES POR PAGAR' => '25',
    'IMPUESTO SORELA RENTA' => '26',
    'PASIVO FIJO' => '27',
    'CUENTAS POR PAGAR A LARGO PLAZO' => '28',
    'PASIVO DIFERIDO' => '29',
    'PATRIMONIO Y CAPITAL' => '3',
    'UTILIDAD O PERDIDA DE PERIODO' => '36',
    'INGRESOS' => '4',
    'VENTAS' => '41',
    'REBAJAS Y DESCUENTO' => '42',
    'GASTOS' => '5',
    'GASTOS DE VENTA' => '51',
    'GASTOS ADMINISTRATIVOS' => '53',
    'GASTOS FINANCIEROS' => '54',
    'GASTOS NO DEDUCIBLES' => '55',
    'GASTOS DE ALMACEN' => '56',
    'COSTO DE VENTA' => '6',
    'OTROS EGRESOS' => '7'
];

$sql = "USE `contafc`;\n";
foreach ($lines as $line) {
    if (preg_match('/^(\d+)\s+(.+)$/', trim($line), $m)) {
        $cod = $m[1];
        $rest = preg_split('/\s{2,}|\t/', $m[2]);
        $nom = trim($rest[0]);
        $sql .= sprintf("UPDATE puc_cuentas SET nombre = '%s' WHERE codigo = '%s' AND empresa_id = 1;\n", addslashes($nom), $cod);
    } else {
        $h = strtoupper(trim($line));
        if (isset($headerToCode[$h])) {
            $cod = $headerToCode[$h];
            $sql .= sprintf("UPDATE puc_cuentas SET nombre = '%s' WHERE codigo = '%s' AND empresa_id = 1;\n", addslashes(trim($line)), $cod);
        }
    }
}

file_put_contents(__DIR__ . '/update_nombres_puc.sql', $sql);
echo "SQL script generated at database/update_nombres_puc.sql\n";
