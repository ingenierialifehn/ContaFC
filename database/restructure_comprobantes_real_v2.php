<?php
$ip = '172.18.0.3';
try {
    $db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Iniciando re-estructuración de comprobantes por fecha...\n";

    // 1. Buscamos todos los asientos cuya fecha no coincide con la de su comprobante
    // Usamos tipo_comp_id (nombre real de la columna en servidor_db)
    $sql = "SELECT a.id, a.fecha as fecha_asiento, a.comprobante_id, c.tipo_comp_id, a.empresa_id
            FROM asientos a
            JOIN comprobantes c ON a.comprobante_id = c.id
            WHERE a.fecha <> c.fecha";
    $stmt = $db->query($sql);
    $orphans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Movimientos a reubicar: " . count($orphans) . "\n";
    
    if (count($orphans) == 0) {
        echo "No hay desajustes. El problema podría ser otro.\n";
        exit;
    }

    // Cache de nuevos comprobantes para evitar duplicados en el mismo proceso
    $compCache = [];

    $count = 0;
    foreach ($orphans as $row) {
        $key = $row['fecha_asiento'] . "_" . $row['tipo_comp_id'] . "_" . $row['empresa_id'];
        
        if (!isset($compCache[$key])) {
            // Crear nuevo comprobante (usando las columnas reales de servidor_db)
            $ins = $db->prepare("INSERT INTO comprobantes (fecha, tipo_comp_id, empresa_id, descripcion, estado, usuario_id, periodo_id, numero) 
                                VALUES (?, ?, ?, ?, 'registrado', 1, 0, 0)");
            $ins->execute([
                $row['fecha_asiento'], 
                $row['tipo_comp_id'], 
                $row['empresa_id'],
                "Sincronización automática de fecha migration"
            ]);
            $compCache[$key] = $db->lastInsertId();
        }
        
        // Mover el asiento al nuevo comprobante
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
