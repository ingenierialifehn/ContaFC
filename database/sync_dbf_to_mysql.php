<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();

echo "Cargando datos del DBF...\n";
$jsonStr = file_get_contents(__DIR__ . '/dbf_data.json');
$dbfRows = json_decode($jsonStr, true);

echo "Obteniendo periodos...\n";
$periodos = [];
$stmt = $db->query("SELECT id, anio, mes FROM periodos WHERE empresa_id = 1");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $periodos[$row['anio'] . '_' . $row['mes']] = $row['id'];
}

echo "Obteniendo registros existentes en MySQL...\n";
$existingConteo = [];
$stmt = $db->query("SELECT conteo FROM asientos WHERE conteo IS NOT NULL");
while ($c = $stmt->fetchColumn()) {
    $existingConteo[(int)$c] = true;
}

echo "Obteniendo cuentas...\n";
$accounts = [];
$stmt = $db->query("SELECT id, codigo FROM puc_cuentas WHERE empresa_id = 1");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $accounts[$row['codigo']] = $row['id'];
}

$comprobantes = [];
$lastLinea = [];
$stmt = $db->query("SELECT id, numero, fecha FROM comprobantes WHERE empresa_id = 1");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Intentamos matchear por TANDA si el numero guardado coincide o por fecha/numero
    $key = $row['fecha'] . '_' . $row['numero'];
    $comprobantes[$key] = $row['id'];
}

$stmt = $db->query("SELECT MAX(numero) FROM comprobantes WHERE empresa_id = 1 AND tipo_comp_id = 1");
$maxNumero = (int)$stmt->fetchColumn();

// Para obtener la última linea de los comprobantes existentes
$stmt = $db->query("SELECT comprobante_id, MAX(linea) as max_linea FROM asientos GROUP BY comprobante_id");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $lastLinea[$row['comprobante_id']] = (int)$row['max_linea'];
}

$missing = 0;
foreach ($dbfRows as $row) {
    if (!isset($existingConteo[(int)$row['conteo']])) {
        $missing++;
    }
}
echo "Registros a insertar: $missing\n";

$inserted = 0;
$db->beginTransaction();

// Mapeo de TANDA_FECHA a Numero de Comprobante en MySQL
$tandaToComp = [];

try {
    foreach ($dbfRows as $row) {
        $conteo = (int)$row['conteo'];
        if (isset($existingConteo[$conteo])) continue;

        $acctCode = $row['acct'];
        if (!isset($accounts[$acctCode])) {
            $d1 = substr($acctCode, 0, 1);
            $tipo = 'G'; $nat = 'D'; $nivel = 4;
            if ($d1 == '1') { $tipo = 'A'; $nat = 'D'; }
            elseif ($d1 == '2') { $tipo = 'P'; $nat = 'C'; }
            elseif ($d1 == '3') { $tipo = 'R'; $nat = 'C'; }
            elseif ($d1 == '4') { $tipo = 'R'; $nat = 'C'; }
            elseif ($d1 == '5') { $tipo = 'G'; $nat = 'D'; }
            
            $stmt = $db->prepare("INSERT INTO puc_cuentas (empresa_id, codigo, nombre, nivel, tipo_cuenta, naturaleza, activa) VALUES (1, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$acctCode, "Cuenta Migrada $acctCode", $nivel, $tipo, $nat]);
            $accounts[$acctCode] = $db->lastInsertId();
        }

        $fecha = $row['fecha'] ?? '2023-01-01';
        $y = (int)substr($fecha, 0, 4);
        $m = (int)substr($fecha, 5, 2);
        $pKey = $y . '_' . $m;
        
        if (!isset($periodos[$pKey])) {
            $stmt = $db->prepare("INSERT INTO periodos (empresa_id, anio, mes, estado) VALUES (1, ?, ?, 'abierto')");
            $stmt->execute([$y, $m]);
            $periodos[$pKey] = $db->lastInsertId();
        }
        $pid = $periodos[$pKey];

        $tanda = $row['tanda'] ?? 1;
        $tandaKey = $fecha . '_' . $tanda;
        
        if (!isset($tandaToComp[$tandaKey])) {
            // Buscamos si ya hay un comprobante para esta fecha y tanda
            // Como no tenemos la tanda en la BD, usamos el numero de comprobante que coincida
            // Pero para nuevos, simplemente incrementamos el global
            $numeroComp = ++$maxNumero;
            $stmt = $db->prepare("INSERT INTO comprobantes (empresa_id, periodo_id, tipo_comp_id, numero, fecha, observaciones, estado, usuario_id) VALUES (1, ?, 1, ?, ?, ?, 'registrado', 1)");
            $stmt->execute([$pid, $numeroComp, $fecha, "Importado de DBF TANDA $tanda"]);
            $tandaToComp[$tandaKey] = $db->lastInsertId();
            $lastLinea[$tandaToComp[$tandaKey]] = 0;
        }
        $compId = $tandaToComp[$tandaKey];
        $linea = ++$lastLinea[$compId];

        $debito = (float)$row['debito'];
        $credito = (float)$row['credito'];
        $desc = $row['desc'] ?? '';
        
        if (stripos($desc, 'intereses') !== false && stripos($desc, 'financiaci') !== false) {
            if ($credito < 0) $credito = abs($credito);
            if ($debito < 0) $debito = abs($debito);
        }

        $stmt = $db->prepare("INSERT INTO asientos (comprobante_id, empresa_id, cuenta_id, linea, debito, credito, descripcion, fecha, conteo) VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$compId, $accounts[$acctCode], $linea, $debito, $credito, $desc, $fecha, $conteo]);
        
        $inserted++;
        if ($inserted % 1000 == 0) echo "Insertados $inserted...\n";
    }
    $db->commit();
    echo "Sincronización completada. Total insertados: $inserted\n";
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
