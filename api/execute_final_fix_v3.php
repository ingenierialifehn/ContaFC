<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "INICIANDO LIMPIEZA GLOBAL DE DUPLICADOS (VERSION CORREGIDA)...\n\n";

$accounts = [
    '11020104', '11020106', '11050130', '11060102', 
    '11060127', '12010106', '21010110', '27010101', '36100101', '11050101'
];

try {
    $db->beginTransaction();

    foreach ($accounts as $code) {
        echo "Limpiando cuenta: $code...\n";
        
        $sql = "DELETE a1 FROM asientos a1
                JOIN puc_cuentas p1 ON a1.cuenta_id = p1.id
                INNER JOIN (
                    SELECT MIN(a2.id) as keep_id, a2.cuenta_id, a2.debito, a2.credito, a2.fecha, TRIM(a2.descripcion) as t_desc
                    FROM asientos a2
                    JOIN puc_cuentas p2 ON a2.cuenta_id = p2.id
                    WHERE p2.codigo = :code1 AND p2.empresa_id = 1
                    GROUP BY a2.cuenta_id, a2.debito, a2.credito, a2.fecha, t_desc
                    HAVING COUNT(*) > 1
                ) dup ON a1.cuenta_id = dup.cuenta_id 
                    AND ROUND(a1.debito, 2) = ROUND(dup.debito, 2)
                    AND ROUND(a1.credito, 2) = ROUND(dup.credito, 2)
                    AND a1.fecha = dup.fecha
                    AND TRIM(a1.descripcion) = dup.t_desc
                WHERE a1.id > dup.keep_id
                  AND p1.codigo = :code2
                  AND p1.empresa_id = 1";
        
        $stmt = $db->prepare($sql);
        // Usamos dos parámetros distintos para el mismo valor
        $stmt->execute([':code1' => $code, ':code2' => $code]);
        $deleted = $stmt->rowCount();
        echo "  -> Duplicados eliminados: $deleted\n";
    }

    $db->commit();
    echo "\nLIMPIEZA GLOBAL COMPLETADA CON ÉXITO.\n";

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
