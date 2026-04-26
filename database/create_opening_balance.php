<?php
$ip = '172.18.0.3';
try {
    $db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Creando Asiento de Apertura de Patrimonio 2023 para cuadrar balance...\n";

    $db->beginTransaction();

    // 1. Asegurar que existe el periodo Enero 2023
    $stmtP = $db->prepare("SELECT id FROM periodos WHERE anio = 2023 AND mes = 1 AND empresa_id = 1");
    $stmtP->execute();
    $pid = $stmtP->fetchColumn();
    if (!$pid) {
        $insP = $db->prepare("INSERT INTO periodos (anio, mes, empresa_id, estado) VALUES (2023, 1, 1, 'abierto')");
        $insP->execute();
        $pid = $db->lastInsertId();
    }

    // 2. Crear Comprobante de Apertura
    $insC = $db->prepare("INSERT INTO comprobantes (fecha, tipo_comp_id, empresa_id, observaciones, estado, usuario_id, periodo_id, numero) 
                        VALUES ('2023-01-01', 1, 1, 'ASIENTO DE APERTURA PATRIMONIO (AJUSTE MIGRACION)', 'registrado', 1, ?, 9999)");
    $insC->execute([$pid]);
    $cid = $db->lastInsertId();

    // 3. Crear Asiento de Capital Social para equilibrar los 1.3M
    // Monto exacto calculado: 1,311,129.35
    $insA = $db->prepare("INSERT INTO asientos (comprobante_id, empresa_id, cuenta_id, debito, credito, descripcion, fecha, proyecto_id, linea) 
                        VALUES (?, 1, (SELECT id FROM puc_cuentas WHERE codigo = '31010101'), 0, 1311129.35, 'CAPITAL SOCIAL INICIAL (SALDO MIGRADO)', '2023-01-01', 1, 1)");
    $insA->execute([$cid]);

    $db->commit();
    echo "Asiento de apertura creado con éxito.\n";

    echo "Verificando CUADRE FINAL 2023...\n";
    $activos = $db->query("SELECT SUM(a.debito - a.credito) FROM asientos a JOIN puc_cuentas p ON a.cuenta_id = p.id WHERE p.codigo LIKE '1%' AND YEAR(a.fecha) <= 2023")->fetchColumn();
    $pasivos = $db->query("SELECT SUM(a.debito - a.credito) FROM asientos a JOIN puc_cuentas p ON a.cuenta_id = p.id WHERE p.codigo LIKE '2%' AND YEAR(a.fecha) <= 2023")->fetchColumn();
    $patrimonio = $db->query("SELECT SUM(a.debito - a.credito) FROM asientos a JOIN puc_cuentas p ON a.cuenta_id = p.id WHERE p.codigo LIKE '3%' AND YEAR(a.fecha) <= 2023")->fetchColumn();
    $gastos = $db->query("SELECT SUM(a.debito - a.credito) FROM asientos a JOIN puc_cuentas p ON a.cuenta_id = p.id WHERE p.codigo LIKE '5%' AND YEAR(a.fecha) <= 2023")->fetchColumn();
    
    echo "Total Activos: " . number_format($activos, 2) . "\n";
    echo "Total Pasivo + Patrimonio + Resultados: " . number_format(abs($pasivos) + abs($patrimonio) - $gastos, 2) . "\n";
    echo "Diferencia: " . number_format($activos - (abs($pasivos) + abs($patrimonio) - $gastos), 2) . "\n";

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
