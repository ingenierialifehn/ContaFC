<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();

echo "LIMPIANDO BASE DE DATOS (DROP ALL TABLES)...\n";

try {
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    // Obtener todas las tablas
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "Eliminando tabla: $table...\n";
        $db->exec("DROP TABLE IF EXISTS `$table` CASCADE");
    }
    
    // Obtener todas las vistas
    $stmt = $db->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
    $views = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($views as $view) {
        echo "Eliminando vista: $view...\n";
        $db->exec("DROP VIEW IF EXISTS `$view` CASCADE");
    }

    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
    
    echo "¡BASE DE DATOS LIMPIA!\n";
    
} catch (Exception $e) {
    echo "ERROR DURANTE LA LIMPIEZA: " . $e->getMessage() . "\n";
}
?>
