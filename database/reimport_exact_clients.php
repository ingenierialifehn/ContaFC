<?php
$ip = '172.18.0.3';
try {
    $db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $jsonStr = file_get_contents(__DIR__ . '/dbf_data.json');
    $dbfRows = json_decode($jsonStr, true);

    echo "Filtrando datos del DBF para la cuenta 11050101 año 2023...\n";
    $toImport = [];
    foreach ($dbfRows as $row) {
        if ($row['acct'] == '11050101' && date('Y', strtotime($row['fecha'])) == '2023') {
            $toImport[] = $row;
        }
    }
    echo "Total registros a re-importar: " . count($toImport) . "\n";

    $db->beginTransaction();

    // 1. Borrar registros actuales de esa cuenta en ese año
    $del = $db->prepare("DELETE FROM asientos WHERE cuenta_id = (SELECT id FROM puc_cuentas WHERE codigo = '11050101') AND YEAR(fecha) = 2023");
    $del->execute();
    echo "Registros antiguos eliminados.\n";

    // 2. Mapeo de periodos y comprobantes para re-vincular
    $map = json_decode(file_get_contents(__DIR__ . '/period_map.json'), true);
    $nextNum = json_decode(file_get_contents(__DIR__ . '/next_nums.json'), true);
    $compCache = [];

    // 3. Re-importar
    $ins = $db->prepare("INSERT INTO asientos (comprobante_id, empresa_id, cuenta_id, debito, credito, descripcion, fecha, proyecto_id) 
                        VALUES (?, ?, (SELECT id FROM puc_cuentas WHERE codigo = '11050101'), ?, ?, ?, ?, 1)");

    foreach ($toImport as $row) {
        $fecha = $row['fecha'];
        $year = (int)date('Y', strtotime($fecha));
        $month = (int)date('n', strtotime($fecha));
        $eid = 1; // Asumimos empresa 1
        $tid = 1; // Tipo de comprobante (Generalmente 1 para migración)

        // Asegurar periodo
        $pid = $map[$eid][$year][$month] ?? null;
        if (!$pid) {
            $insP = $db->prepare("INSERT INTO periodos (anio, mes, empresa_id, estado) VALUES (?, ?, ?, 'abierto')");
            $insP->execute([$year, $month, $eid]);
            $pid = $db->lastInsertId();
            $map[$eid][$year][$month] = $pid;
        }

        // Asegurar comprobante por fecha
        $key = $fecha . "_" . $tid;
        if (!isset($compCache[$key])) {
            $num = $nextNum[$eid][$tid] ?? 1;
            $nextNum[$eid][$tid] = $num + 1;
            
            $insC = $db->prepare("INSERT INTO comprobantes (fecha, tipo_comp_id, empresa_id, observaciones, estado, usuario_id, periodo_id, numero) 
                                VALUES (?, ?, ?, 'Re-importación exacta de Clientes 2023', 'registrado', 1, ?, ?)");
            $insC->execute([$fecha, $tid, $eid, $pid, $num]);
            $compCache[$key] = $db->lastInsertId();
        }

        // Lógica de intereses y signos
        $debito = (float)$row['debito'];
        $credito = (float)$row['credito'];

        // Si el crédito es negativo (intereses que sumaban), lo pasamos a débito positivo
        if ($credito < 0) {
            $debito += abs($credito);
            $credito = 0;
        }

        $ins->execute([
            $compCache[$key],
            $eid,
            $debito,
            $credito,
            $row['detalle'],
            $fecha
        ]);
    }

    $db->commit();
    echo "Re-importación completada con éxito.\n";

    echo "Verificando Balance FINAL 11050101...\n";
    $finalBalance = $db->query("SELECT SUM(debito - credito) FROM asientos WHERE cuenta_id = (SELECT id FROM puc_cuentas WHERE codigo = '11050101') AND YEAR(fecha) = 2023")->fetchColumn();
    echo "Saldo MySQL Final: " . number_format((float)$finalBalance, 2) . "\n";
    echo "Saldo Legacy Esperado: 1,147,233.74\n";

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
