<?php
$ip = '172.18.0.3';
try {
    $db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $map = json_decode(file_get_contents(__DIR__ . '/period_map.json'), true);
    $nextNum = json_decode(file_get_contents(__DIR__ . '/next_nums.json'), true);

    echo "Iniciando re-estructuración de comprobantes por fecha (v5)...\n";

    $sql = "SELECT a.id, a.fecha as fecha_asiento, a.comprobante_id, c.tipo_comp_id, a.empresa_id
            FROM asientos a
            JOIN comprobantes c ON a.comprobante_id = c.id
            WHERE a.fecha <> c.fecha";
    $stmt = $db->query($sql);
    $orphans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Movimientos a reubicar: " . count($orphans) . "\n";
    
    if (count($orphans) == 0) {
        echo "No hay desajustes.\n";
        exit;
    }

    $compCache = [];
    $count = 0;
    foreach ($orphans as $row) {
        $fecha = $row['fecha_asiento'];
        $year = (int)date('Y', strtotime($fecha));
        $month = (int)date('n', strtotime($fecha));
        $eid = $row['empresa_id'];
        $tid = $row['tipo_comp_id'];
        
        $pid = $map[$eid][$year][$month] ?? null;
        if (!$pid) {
            $insP = $db->prepare("INSERT INTO periodos (anio, mes, empresa_id, estado) VALUES (?, ?, ?, 'abierto')");
            $insP->execute([$year, $month, $eid]);
            $pid = $db->lastInsertId();
            $map[$eid][$year][$month] = $pid;
        }

        $key = $fecha . "_" . $tid . "_" . $eid;
        
        if (!isset($compCache[$key])) {
            $num = $nextNum[$eid][$tid] ?? 1;
            $nextNum[$eid][$tid] = $num + 1;

            $ins = $db->prepare("INSERT INTO comprobantes (fecha, tipo_comp_id, empresa_id, observaciones, estado, usuario_id, periodo_id, numero) 
                                VALUES (?, ?, ?, ?, 'registrado', 1, ?, ?)");
            $ins->execute([
                $fecha, 
                $tid, 
                $eid,
                "Ajuste automático de migración por fecha",
                $pid,
                $num
            ]);
            $compCache[$key] = $db->lastInsertId();
        }
        
        $upd = $db->prepare("UPDATE asientos SET comprobante_id = ? WHERE id = ?");
        $upd->execute([$compCache[$key], $row['id']]);
        $count++;
    }

    echo "Total asientos reubicados: $count\n";
    echo "Nuevos comprobantes creados: " . count($compCache) . "\n";

    echo "Verificando Balance FINAL Activos 2023...\n";
    $sqlBalance = "SELECT SUM(a.debito - a.credito) 
                   FROM asientos a 
                   JOIN puc_cuentas p ON a.cuenta_id = p.id 
                   JOIN comprobantes c ON a.comprobante_id = c.id
                   WHERE p.codigo LIKE '1%' AND YEAR(c.fecha) <= 2023";
    $finalBalance = $db->query($sqlBalance)->fetchColumn();
    echo "Balance Activos 2023 RECALCULADO: " . number_format((float)$finalBalance, 2) . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
