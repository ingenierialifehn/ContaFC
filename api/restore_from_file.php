<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();
$backupFile = __DIR__ . '/../backups/contafc-27Abril2026.sql';

echo "INICIANDO RESTAURACIÓN PHP DESDE: $backupFile\n";

if (!file_exists($backupFile)) {
    die("ERROR: El archivo de respaldo no existe.\n");
}

try {
    // Aumentar límites para archivos grandes
    set_time_limit(600);
    ini_set('memory_limit', '512M');

    $sql = file_get_contents($backupFile);
    
    // Ejecutar el SQL (usando exec para consultas múltiples si es posible, 
    // pero PDO::exec solo ejecuta una. Usaremos el método nativo de PDO si está disponible o dividiremos)
    // Para archivos grandes de respaldo, lo mejor es usar el método nativo de exec del servidor si se puede,
    // o procesar por bloques.
    
    $db->exec($sql);
    
    echo "¡RESTAURACIÓN COMPLETADA EXITOSAMENTE!\n";
    
} catch (Exception $e) {
    echo "ERROR DURANTE LA RESTAURACIÓN: " . $e->getMessage() . "\n";
}
?>
