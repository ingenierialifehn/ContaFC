<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();
$backupFile = __DIR__ . '/../backups/contafc-27Abril2026.sql';

echo "INICIANDO RESTAURACIÓN AVANZADA PHP...\n";

if (!file_exists($backupFile)) {
    die("ERROR: El archivo no existe.\n");
}

try {
    set_time_limit(900);
    ini_set('memory_limit', '512M');

    $sql = file_get_contents($backupFile);
    
    // Limpiar DELIMITER
    $sql = preg_replace('/DELIMITER \$\$/i', '', $sql);
    $sql = preg_replace('/DELIMITER ;/i', '', $sql);
    $sql = str_replace('$$', ';', $sql); // Reemplazar el delimitador especial por ;

    // Dividir por ; pero ignorar los que están dentro de comillas o bloques
    // Para simplificar, usaremos un método de división por líneas
    $queries = explode(';', $sql);
    
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    $count = 0;
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query)) continue;
        
        try {
            $db->exec($query);
            $count++;
        } catch (Exception $e) {
            // Ignorar errores menores en comentarios o duplicados
            if (strpos($e->getMessage(), 'Empty query') === false) {
                echo "Aviso en consulta $count: " . substr($query, 0, 50) . "... -> " . $e->getMessage() . "\n";
            }
        }
    }
    
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
    
    echo "\n¡RESTAURACIÓN COMPLETADA! Se ejecutaron $count instrucciones SQL.\n";
    
} catch (Exception $e) {
    echo "ERROR CRÍTICO: " . $e->getMessage() . "\n";
}
?>
