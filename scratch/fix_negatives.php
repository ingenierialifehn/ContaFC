<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();

try {
    $db->beginTransaction();

    echo "⚙️ Preparando entorno...\n";
    // Deshabilitar modo estricto para permitir valores negativos si aplica
    $db->exec("SET sql_mode = ''");

    // 1. Asegurar cuenta 11050101
    echo "📦 Asegurando cuenta 11050101...\n";
    $db->exec("INSERT IGNORE INTO puc_cuentas (empresa_id, codigo, nombre, nivel, naturaleza, tipo_cuenta, acepta_movimiento) 
               VALUES (1, '11050101', 'Intereses por Cobrar (Migrados)', 4, 'D', 'A', 1)");

    // 2. Asegurar tipo de comprobante MIG
    $db->exec("INSERT IGNORE INTO tipos_comprobante (id, empresa_id, codigo, nombre, activo) VALUES (99, 1, 'MIG', 'Migración Legacy DBF', 1)");

    // 3. Asegurar periodos 2025
    echo "📅 Asegurando periodos 2025...\n";
    for ($m = 1; $m <= 12; $m++) {
        $db->exec("INSERT IGNORE INTO periodos (empresa_id, anio, mes, estado) VALUES (1, 2025, $m, 'abierto')");
    }

    // 4. Asegurar comprobantes paraguas
    echo "📂 Asegurando comprobantes paraguas...\n";
    $meses = [1, 2, 4, 7, 9];
    foreach ($meses as $m) {
        $id = 10000 + $m;
        $db->exec("INSERT IGNORE INTO comprobantes (id, empresa_id, tipo_comp_id, numero, fecha, periodo_id, usuario_id, estado, observaciones) 
                   VALUES ($id, 1, 99, $id, '2025-$m-01', (SELECT id FROM periodos WHERE anio=2025 AND mes=$m LIMIT 1), 1, 'registrado', 'Migracion DBF 2025-$m (Fix Negativos)')");
    }

    // 5. Insertar los asientos negativos (Intereses Financiación con crédito negativo)
    echo "✍️ Insertando asientos con créditos negativos...\n";

    // Primero asegurar terceros
    $terceros = [
        'NOLVIA YOLANY VILLANUEVA CRUZ', 'LOURDES ANTONIA GAMEZ HERNANDEZ', 'ALBA ROSA VELASQUEZ MORALES',
        'DENNIS STIVEN ROMERO SAGASTUME', 'YEFRY JOEL PADILLA GIRON', 'MARIA ALMA RIVERA MEJIA',
        'EDY JOSUE PALMA BANEGAS', 'JOSE MANUEL TORRES SANCHEZ', 'TANIA ALEJANDRA ROSENBROCK SANCHEZ',
        'MARIA CELESTE LOPEZ LOPEZ', 'LUCIA GRICELDA SORIANO', 'ANYELI BANESSA AGUILAR MEJIA',
        'ELVA ERNESTINA MARQUEZ ULLOA', 'GERSON CAMILO MENDOZA  AVILA', 'BERCI LLANELI LOPEZ NUÑEZ',
        'REINA ISABEL NUÑEZ OLIVA', 'OSCAR DANIEL ROBLES REYES', 'CHRISTIAN ARMANDO LOPEZ NUÑEZ',
        'YEIRY DANEY BUSTILLO'
    ];

    foreach ($terceros as $t) {
        $code = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($t)), 0, 10);
        $stmt = $db->prepare("INSERT IGNORE INTO terceros (empresa_id, codigo, razon_social, nit_cc, tipo_documento, tipo_tercero) VALUES (1, ?, ?, '', 'RTN', 'cliente')");
        $stmt->execute([$code, $t]);
    }

    $cols = "empresa_id, comprobante_id, linea, fecha, cuenta_id, tercero_id, debito, credito, descripcion, doc_cruce_tipo, doc_cruce_num, conteo";

    // Datos: (empresa_id, comprobante_id, linea, fecha, cuenta_id via SELECT, tercero_id via SELECT, debito, credito, descripcion, doc_cruce_tipo, doc_cruce_num, conteo)
    $asientos = [
        [10001, 33,   '2025-01-03', 'NOLVIA YOLANY VILLANUEVA CRUZ',       0.0000, -9.9960,  'Intereses Financiación', 'FI', '170', 22234],
        [10001, 178,  '2025-01-06', 'LOURDES ANTONIA GAMEZ HERNANDEZ',     0.0000, -20.0040, 'Intereses Financiación', 'FI', '153', 22370],
        [10001, 500,  '2025-01-17', 'ALBA ROSA VELASQUEZ MORALES',         0.0000, -15.0000, 'Intereses Financiación', 'FI', '175', 22616],
        [10001, 876,  '2025-01-28', 'DENNIS STIVEN ROMERO SAGASTUME',      0.0000, -9.9960,  'Intereses Financiación', 'FI', '155', 22926],
        [10002, 1058, '2025-02-01', 'YEFRY JOEL PADILLA GIRON',            0.0000, -50.0040, 'Intereses Financiación', 'FI', '156', 23076],
        [10002, 1726, '2025-02-24', 'MARIA ALMA RIVERA MEJIA',             0.0000, -9.9960,  'Intereses Financiación', 'FI', '172', 23643],
        [10002, 1937, '2025-02-27', 'EDY JOSUE PALMA BANEGAS',             0.0000, -50.0040, 'Intereses Financiación', 'FI', '173', 23817],
        [10002, 2064, '2025-02-28', 'JOSE MANUEL TORRES SANCHEZ',          0.0000, -50.0040, 'Intereses Financiación', 'FI', '167', 23941],
        [10004, 2619, '2025-04-04', 'TANIA ALEJANDRA ROSENBROCK SANCHEZ',  0.0000, -50.0040, 'Intereses Financiación', 'FI', '176', 24284],
        [10004, 2826, '2025-04-14', 'MARIA CELESTE LOPEZ LOPEZ',           0.0000, -50.0040, 'Intereses Financiación', 'FI', '177', 24447],
        [10004, 3066, '2025-04-23', 'LUCIA GRICELDA SORIANO',              0.0000, -50.0040, 'Intereses Financiación', 'FI', '178', 24638],
        [10007, 5003, '2025-07-26', 'ANYELI BANESSA AGUILAR MEJIA',        0.0000, -50.0040, 'Intereses Financiación', 'FI', '181', 26054],
        [10007, 5169, '2025-07-30', 'ELVA ERNESTINA MARQUEZ ULLOA',        0.0000, -50.0040, 'Intereses Financiación', 'FI', '182', 26203],
        [10009, 5745, '2025-09-02', 'GERSON CAMILO MENDOZA  AVILA',        0.0000, -30.0000, 'Intereses Financiación', 'FI', '189', 26563],
        [10009, 5961, '2025-09-10', 'BERCI LLANELI LOPEZ NUÑEZ',           0.0000, -50.0040, 'Intereses Financiación', 'FI', '190', 26743],
        [10009, 6083, '2025-09-10', 'REINA ISABEL NUÑEZ OLIVA',            0.0000, -50.0040, 'Intereses Financiación', 'FI', '191', 26865],
        [10009, 6205, '2025-09-10', 'REINA ISABEL NUÑEZ OLIVA',            0.0000, -50.0040, 'Intereses Financiación', 'FI', '192', 26987],
        [10009, 6327, '2025-09-10', 'OSCAR DANIEL ROBLES REYES',           0.0000, -50.0040, 'Intereses Financiación', 'FI', '193', 27109],
        [10009, 6449, '2025-09-10', 'CHRISTIAN ARMANDO LOPEZ NUÑEZ',       0.0000, -50.0040, 'Intereses Financiación', 'FI', '194', 27231],
        [10009, 6806, '2025-09-12', 'YEIRY DANEY BUSTILLO',                0.0000, -24.9960, 'Intereses Financiación', 'FI', '196', 27477],
    ];

    $stmtCuenta  = $db->prepare("SELECT id FROM puc_cuentas WHERE codigo='11050101' AND empresa_id=1 LIMIT 1");
    $stmtTercero = $db->prepare("SELECT id FROM terceros WHERE razon_social=? AND empresa_id=1 LIMIT 1");
    $stmtInsert  = $db->prepare(
        "INSERT IGNORE INTO asientos ($cols) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmtCuenta->execute();
    $cuentaId = $stmtCuenta->fetchColumn();
    if (!$cuentaId) {
        throw new Exception("No se encontró la cuenta 11050101");
    }
    echo "✅ cuenta_id para 11050101: $cuentaId\n";

    $ok = 0; $skip = 0;
    foreach ($asientos as $a) {
        [$compId, $linea, $fecha, $terceroNombre, $debito, $credito, $desc, $docTipo, $docNum, $conteo] = $a;

        $stmtTercero->execute([$terceroNombre]);
        $terceroId = $stmtTercero->fetchColumn() ?: null;

        $stmtInsert->execute([
            $compId, $linea, $fecha, $cuentaId, $terceroId,
            $debito, $credito, $desc, $docTipo, $docNum, $conteo
        ]);

        if ($stmtInsert->rowCount() > 0) { $ok++; } else { $skip++; }
    }


    $db->commit();
    echo "✅ Éxito: Se han insertado los asientos con créditos negativos correctamente.\n";

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
