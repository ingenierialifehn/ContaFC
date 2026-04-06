<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Database;

if (php_sapi_name() !== 'cli' && !isset($_GET['run'])) {
    die("Corra este script desde CLI o agregue ?run=1 a la URL\n");
}

$db = Database::getInstance()->getPdo();
$eid = 1; // Empresa default

$filePath = __DIR__ . '/cuentas.txt';
if (!file_exists($filePath)) {
    die("Error: No se encontró cuentas.txt\n");
}

$lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

/**
 * Mapeo de encabezados a códigos (si no tienen)
 */
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

$items = [];
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;

    if (preg_match('/^(\d+)\s+(.+)$/', $line, $matches)) {
        $codigo = (string)$matches[1];
        $rest = $matches[2];
        $parts = preg_split('/\s{2,}|\t/', $rest);
        $nombre = trim($parts[0]);
        
        $items[$codigo] = [
            'codigo' => $codigo,
            'nombre' => $nombre
        ];
    } else {
        $header = strtoupper($line);
        if (isset($headerToCode[$header])) {
            $codigo = (string)$headerToCode[$header];
            $items[$codigo] = [
                'codigo' => $codigo,
                'nombre' => $line
            ];
        }
    }
}

function getNivel($cod) {
    $c = (string)$cod;
    $len = strlen($c);
    if ($len == 1) return 1;
    if ($len == 2) return 2;
    if ($len <= 4) return 3;
    if ($len <= 6) return 4;
    return 5;
}

function getPadre($cod) {
    $c = (string)$cod;
    $len = strlen($c);
    if ($len == 1) return null;
    if ($len == 2) return substr($c, 0, 1);
    if ($len == 4) return substr($c, 0, 2);
    if ($len == 6) return substr($c, 0, 4);
    if ($len == 8) return substr($c, 0, 6);
    return substr($c, 0, -1);
}

$allCuentas = [];
foreach ($items as $codigo => $data) {
    $curr = (string)$codigo;
    while ($curr !== '') {
        if (!isset($allCuentas[$curr])) {
            $nombre = isset($items[$curr]) ? $items[$curr]['nombre'] : "Grupo " . $curr;
            $nivel = getNivel($curr);
            $padre = getPadre($curr);
            
            $firstDigit = $curr[0];
            $naturaleza = 'D';
            $tipo = 'A';
            switch ($firstDigit) {
                case '1': $naturaleza = 'D'; $tipo = 'A'; break;
                case '2': $naturaleza = 'C'; $tipo = 'P'; break;
                case '3': $naturaleza = 'C'; $tipo = 'R'; break;
                case '4': $naturaleza = 'C'; $tipo = 'R'; break;
                case '5': $naturaleza = 'D'; $tipo = 'G'; break;
                case '6': $naturaleza = 'D'; $tipo = 'G'; break;
                case '7': $naturaleza = 'D'; $tipo = 'G'; break;
            }
            if (stripos($nombre, 'Deprec. Acum') !== false) $naturaleza = 'C';
            if (stripos($nombre, 'Reserva') !== false && $firstDigit == '1') $naturaleza = 'C';

            $allCuentas[$curr] = [
                'codigo' => $curr,
                'nombre' => $nombre,
                'nivel' => (int)$nivel,
                'codigo_padre' => $padre,
                'naturaleza' => $naturaleza,
                'tipo' => $tipo,
                'acepta_mov' => ($nivel >= 4) ? 1 : 0
            ];
        }
        $curr = (string)getPadre($curr);
    }
}

try {
    $db->beginTransaction();
    $db->prepare("DELETE FROM puc_cuentas WHERE empresa_id = ?")->execute([$eid]);
    $stmt = $db->prepare("
        INSERT INTO puc_cuentas (empresa_id, codigo, nombre, nivel, codigo_padre, naturaleza, tipo_cuenta, acepta_movimiento)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    ksort($allCuentas);
    foreach ($allCuentas as $row) {
        $stmt->execute([
            $eid, 
            $row['codigo'], 
            $row['nombre'], 
            $row['nivel'], 
            $row['codigo_padre'],
            $row['naturaleza'], 
            $row['tipo'], 
            $row['acepta_mov']
        ]);
    }
    $db->commit();
    echo "Se han insertado " . count($allCuentas) . " cuentas exitosamente.\n";
} catch (\Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
