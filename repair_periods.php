<?php
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;

try {
    $db = Database::getInstance()->getPdo();
    $empresaId = 1;

    ob_start();
    echo "Iniciando reparación de periodos y mapeo de comprobantes...\n";

    // 1. Encontrar años/meses únicos en comprobantes que NO tienen periodo correspondiente
    $sql = "SELECT DISTINCT YEAR(fecha) as anio, MONTH(fecha) as mes 
            FROM comprobantes c
            WHERE c.empresa_id = :eid1
            AND NOT EXISTS (
                SELECT 1 FROM periodos p 
                WHERE p.empresa_id = :eid2 
                AND p.anio = YEAR(c.fecha) 
                AND p.mes = MONTH(c.fecha)
            )";

    $stmt = $db->prepare($sql);
    $stmt->execute([':eid1' => $empresaId, ':eid2' => $empresaId]);
    $missing = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($missing) > 0) {
        echo "Se detectaron " . count($missing) . " periodos faltantes en la tabla maestra. Creándolos...\n";
        $ins = $db->prepare("INSERT INTO periodos (empresa_id, anio, mes, estado) VALUES (:eid, :anio, :mes, 'abierto')");
        foreach ($missing as $m) {
            $ins->execute([':eid' => $empresaId, ':anio' => $m['anio'], ':mes' => $m['mes']]);
            echo " - Creado periodo: {$m['mes']}/{$m['anio']}\n";
        }
    } else {
        echo "No hay periodos faltantes en la tabla maestra.\n";
    }

    echo "Sincronizando periodo_id en todos los comprobantes...\n";
    $upd = $db->prepare("
        UPDATE comprobantes c
        JOIN periodos p ON p.empresa_id = c.empresa_id 
                       AND p.anio = YEAR(c.fecha) 
                       AND p.mes = MONTH(c.fecha)
        SET c.periodo_id = p.id
        WHERE c.empresa_id = :eid
    ");
    $upd->execute([':eid' => $empresaId]);

    echo "¡Reparación completada!\n";
    $out = ob_get_clean();
    file_put_contents('/var/www/html/contafc/repair_log.txt', $out);
    echo "OK: Ver repair_log.txt";

} catch (Throwable $e) {
    file_put_contents('/var/www/html/contafc/repair_error.txt', $e->getMessage() . "\n" . $e->getTraceAsString());
    echo "ERROR: Ver repair_error.txt";
}
