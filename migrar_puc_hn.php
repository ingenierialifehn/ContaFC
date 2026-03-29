<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use ContaFC\Core\Database;

if (php_sapi_name() !== 'cli' && !isset($_GET['run'])) {
    die("Corra este script desde CLI o agregue ?run=1 a la URL");
}

$db = Database::getInstance()->getPdo();
$eid = 1; // Empresa default

try {
    $db->beginTransaction();

    // 1. Limpiar PUC actual (OJO: Esto solo funciona si no hay asientos vinculados aún)
    // Si hay asientos, fallará por integridad referencial.
    $db->prepare("DELETE FROM puc_cuentas WHERE empresa_id = :eid")->execute([':eid' => $eid]);

    $puc = [
        // Clase 1 - Activos
        ['1',      'ACTIVO',                          1, null,   'D', 'A', 0],
        ['11',     'ACTIVO CORRIENTE',                2, '1',    'D', 'A', 0],
        ['1101',   'EFECTIVO Y EQUIVALENTES',         3, '11',   'D', 'A', 0],
        ['110101', 'Caja General',                    4, '1101', 'D', 'A', 1],
        ['110102', 'Bancos - Cuentas de Cheques',     4, '1101', 'D', 'A', 1],
        ['1103',   'CUENTAS POR COBRAR',              3, '11',   'D', 'A', 0],
        ['110301', 'Clientes Nacionales',             4, '1103', 'D', 'A', 1],
        ['110302', 'Documentos por Cobrar',           4, '1103', 'D', 'A', 1],
        ['1104',   'INVENTARIOS',                     3, '11',   'D', 'A', 0],
        ['110401', 'Mercaderías',                     4, '1104', 'D', 'A', 1],
        ['1105',   'PAGOS ANTICIPADOS',               3, '11',   'D', 'A', 0],
        ['110501', 'Rentas Pagadas por Adelantado',   4, '1105', 'D', 'A', 1],
        
        ['12',     'ACTIVO NO CORRIENTE',             2, '1',    'D', 'A', 0],
        ['1201',   'PROPIEDAD, PLANTA Y EQUIPO',      3, '12',   'D', 'A', 0],
        ['120101', 'Terrenos',                        4, '1201', 'D', 'A', 1],
        ['120102', 'Edificios',                       4, '1201', 'D', 'A', 1],
        ['120103', 'Mobiliario y Equipo',             4, '1201', 'D', 'A', 1],
        ['120104', 'Equipo de Computación',            4, '1201', 'D', 'A', 1],
        ['120105', 'Vehículos',                       4, '1201', 'D', 'A', 1],
        ['1202',   'DEPRECIACIÓN ACUMULADA (CR)',     3, '12',   'C', 'A', 1],

        // Clase 2 - Pasivos
        ['2',      'PASIVO',                          1, null,   'C', 'P', 0],
        ['21',     'PASIVO CORRIENTE',                2, '2',    'C', 'P', 0],
        ['2101',   'CUENTAS Y DOC. POR PAGAR',        3, '21',   'C', 'P', 0],
        ['210101', 'Proveedores Nacionales',          4, '2101', 'C', 'P', 1],
        ['2102',   'PRÉSTAMOS BANCARIOS',             3, '21',   'C', 'P', 1],
        ['2103',   'OBLIGACIONES FISCALES',           3, '21',   'C', 'P', 0],
        ['210301', 'ISV por Pagar (15%)',             4, '2103', 'C', 'P', 1],
        ['210302', 'Retenciones por Pagar',           4, '2103', 'C', 'P', 1],

        ['22',     'PASIVO NO CORRIENTE',             2, '2',    'C', 'P', 0],
        ['2201',   'Préstamos a Largo Plazo',         3, '22',   'C', 'P', 1],

        // Clase 3 - Patrimonio
        ['3',      'PATRIMONIO NETO',                 1, null,   'C', 'R', 0],
        ['31',     'CAPITAL',                         2, '3',    'C', 'R', 0],
        ['3101',   'Capital Social',                  3, '31',   'C', 'R', 1],
        ['32',     'RESERVAS',                        2, '3',    'C', 'R', 1],
        ['33',     'RESULTADOS ACUMULADOS',           2, '3',    'C', 'R', 0],
        ['3301',   'Utilidades de Ejercicios Ant.',   3, '33',   'C', 'R', 1],
        ['3302',   'Utilidad del Ejercicio',          3, '33',   'C', 'R', 1],

        // Clase 4 - Ingresos
        ['4',      'INGRESOS',                        1, null,   'C', 'R', 0],
        ['41',     'INGRESOS OPERATIVOS',             2, '4',    'C', 'R', 0],
        ['4101',   'Ventas de Mercaderías',           3, '41',   'C', 'R', 1],
        ['4102',   'Prestación de Servicios',         3, '41',   'C', 'R', 1],
        ['42',     'OTROS INGRESOS',                  2, '4',    'C', 'R', 1],

        // Clase 5 - Gastos
        ['5',      'GASTOS',                          1, null,   'D', 'G', 0],
        ['51',     'GASTOS DE OPERACIÓN',             2, '5',    'D', 'G', 0],
        ['5101',   'Gastos de Venta',                 3, '51',   'D', 'G', 1],
        ['5102',   'Gastos de Administración',        3, '51',   'D', 'G', 0],
        ['510201', 'Sueldos y Salarios',              4, '5102', 'D', 'G', 1],
        ['510202', 'Seguridad Social (IHSS/RAP)',     4, '5102', 'D', 'G', 1],
        ['510203', 'Alquileres',                      4, '5102', 'D', 'G', 1],
        ['510204', 'Servicios Públicos',              4, '5102', 'D', 'G', 1],
        ['510205', 'Papelería y Útiles',              4, '5102', 'D', 'G', 1],

        // Clase 6 - Costos
        ['6',      'COSTOS',                          1, null,   'D', 'G', 0],
        ['61',     'COSTO DE VENTAS',                 2, '6',    'D', 'G', 1],
    ];

    $stmt = $db->prepare(
        "INSERT INTO puc_cuentas (empresa_id, codigo, nombre, nivel, codigo_padre, naturaleza, tipo_cuenta, acepta_movimiento)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    foreach ($puc as $row) {
        $stmt->execute([$eid, $row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6]]);
    }

    $db->commit();
    echo "PUC actualizado a Honduras exitosamente.\n";

} catch (\Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
