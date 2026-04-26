<?php
$ip = '172.18.0.3';
try {
    $db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Iniciando reestructuración masiva para 2023, 2024 y 2025...\n";

    // 1. Identificar asientos cuya fecha no coincide con su comprobante padre
    $sql = "SELECT a.id, a.fecha as fecha_asiento, c.fecha as fecha_comprobante, c.tipo_comp_id, a.empresa_id
            FROM asientos a
            JOIN comprobantes c ON a.comprobante_id = c.id
            WHERE a.fecha <> c.fecha
            AND YEAR(a.fecha) IN (2023, 2024, 2025)";
    
    $mismatches = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    echo "Se encontraron " . count($mismatches) . " asientos con fechas inconsistentes.\n";

    if (count($mismatches) > 0) {
        $count = 0;
        foreach ($mismatches as $m) {
            $fecha = $m['fecha_asiento'];
            $anio = date('Y', strtotime($fecha));
            $mes = (int)date('m', strtotime($fecha));
            $eid = $m['empresa_id'];
            $tipo = $m['tipo_comp_id'];

            // Buscar o crear periodo
            $stmtP = $db->prepare("SELECT id FROM periodos WHERE anio = ? AND mes = ? AND empresa_id = ?");
            $stmtP->execute([$anio, $mes, $eid]);
            $pid = $stmtP->fetchColumn();
            if (!$pid) {
                $db->prepare("INSERT INTO periodos (anio, mes, empresa_id, estado) VALUES (?, ?, ?, 'abierto')")->execute([$anio, $mes, $eid]);
                $pid = $db->lastInsertId();
            }

            // Buscar o crear comprobante para esa fecha exacta y tipo
            $stmtC = $db->prepare("SELECT id FROM comprobantes WHERE fecha = ? AND tipo_comp_id = ? AND empresa_id = ? LIMIT 1");
            $stmtC->execute([$fecha, $tipo, $eid]);
            $cid = $stmtC->fetchColumn();

            if (!$cid) {
                // Obtener siguiente número para este tipo/empresa
                $stmtNum = $db->prepare("SELECT MAX(numero) FROM comprobantes WHERE empresa_id = ? AND tipo_comp_id = ?");
                $stmtNum->execute([$eid, $tipo]);
                $num = (int)$stmtNum->fetchColumn() + 1;
                if ($num < 1000) $num = 1001; // Empezar rango alto para evitar colisiones con manuales

                $insC = $db->prepare("INSERT INTO comprobantes (fecha, tipo_comp_id, empresa_id, observaciones, estado, usuario_id, periodo_id, numero) 
                                    VALUES (?, ?, ?, 'REESTRUCTURACION AUTOMATICA (MIGRACION)', 'registrado', 1, ?, ?)");
                $insC->execute([$fecha, $tipo, $eid, $pid, $num]);
                $cid = $db->lastInsertId();
            }

            // Mover el asiento al nuevo comprobante
            $db->prepare("UPDATE asientos SET comprobante_id = ? WHERE id = ?")->execute([$cid, $m['id']]);
            $count++;
            if ($count % 500 == 0) echo "Procesados $count...\n";
        }
        echo "Reestructuración completada: $count asientos movidos.\n";
    }

    // 2. Limpieza de comprobantes que quedaron vacíos
    $db->exec("DELETE FROM comprobantes WHERE id NOT IN (SELECT DISTINCT comprobante_id FROM asientos)");
    echo "Comprobantes vacíos eliminados.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
