<?php
require_once __DIR__ . '/../bootstrap.php';
// Usaremos mysqli para una restauración más robusta de archivos SQL
$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASSWORD;
$dbname = DB_NAME;

$mysqli = new mysqli($host, $user, $pass, $dbname);

if ($mysqli->connect_error) {
    die("Conexión fallida: " . $mysqli->connect_error);
}

$backupFile = __DIR__ . '/../backups/contafc-27Abril2026.sql';

echo "INICIANDO RESTAURACIÓN NATIVA (mysqli_multi_query)...\n";

try {
    set_time_limit(900);
    ini_set('memory_limit', '1024M');

    // 1. Limpieza total
    $mysqli->query("SET FOREIGN_KEY_CHECKS = 0;");
    $result = $mysqli->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $mysqli->query("DROP TABLE IF EXISTS `{$row[0]}` CASCADE");
    }
    
    // 2. Cargar el archivo SQL completo
    $sql = file_get_contents($backupFile);
    
    // Eliminar comandos de DELIMITER (multi_query no los necesita)
    $sql = preg_replace('/DELIMITER \$\$/i', '', $sql);
    $sql = preg_replace('/DELIMITER ;/i', '', $sql);
    $sql = str_replace('$$', ';', $sql);

    if ($mysqli->multi_query($sql)) {
        $i = 0;
        do {
            $i++;
            // Consumir todos los resultados para evitar errores de 'out of sync'
            if ($result = $mysqli->store_result()) {
                $result->free();
            }
        } while ($mysqli->next_result());
        
        echo "¡RESTAURACIÓN NATIVA COMPLETADA! Se procesaron $i bloques de consultas.\n";
    } else {
        echo "ERROR EN MULTI_QUERY: " . $mysqli->error . "\n";
    }

    $mysqli->query("SET FOREIGN_KEY_CHECKS = 1;");
    $mysqli->close();

} catch (Exception $e) {
    echo "ERROR CRÍTICO: " . $e->getMessage() . "\n";
}
?>
