<?php
// Script de Restauración Maestra desde SQL Original (Protegiendo CxC)
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();

$sqlPath = __DIR__ . '/../database/migracion_asientos.sql';
if (!file_exists($sqlPath)) {
    die("Error: No se encontró el archivo $sqlPath\n");
}
$handle = fopen($sqlPath, "r");

echo "Iniciando restauración desde SQL original...\n";

// Limpiar asientos previos del comprobante de migración
// Borramos todo lo que NO sea CxC en ese comprobante para evitar duplicados al re-poblar
$db->exec("DELETE FROM asientos WHERE comprobante_id = 9999 AND cuenta_id NOT IN (SELECT id FROM puc_cuentas WHERE codigo IN ('11050101', '110301'))");

$db->exec("SET foreign_key_checks = 0");

$linesProcessed = 0;
$inserted = 0;
$skippedCxC = 0;
$currentHeader = "INSERT IGNORE INTO `asientos` (`empresa_id`, `comprobante_id`, `linea`, `credito`, `cuenta_id`, `debito`, `descripcion`, `doc_cruce_num`, `fecha`, `tercero_id`) VALUES ";

while (($line = fgets($handle)) !== false) {
    $linesProcessed++;
    $line = trim($line);
    if (empty($line)) continue;
    
    // Si la línea contiene el nombre de la tabla asientos
    if (strpos($line, "INSERT IGNORE INTO `asientos`") !== false || (strpos($line, "(") === 0 && strpos($line, "9999") !== false)) {
        
        // PROTECCIÓN: Si la línea menciona las cuentas de clientes, la saltamos
        if (strpos($line, "'11050101'") !== false || strpos($line, "'110301'") !== false) {
            $skippedCxC++;
            continue;
        }

        $query = $line;
        // Si la línea es solo valores (multi-insert), añadir el header
        if (strpos($query, "INSERT") === false) {
            $query = $currentHeader . $query;
        }
        
        // Corregir terminación para que sea una sentencia válida individual
        if (substr($query, -1) == ',') $query = substr($query, 0, -1) . ';';
        if (substr($query, -1) != ';') $query .= ';';

        try {
            if (strpos($query, "INSERT IGNORE INTO `asientos`") !== false) {
                $db->exec($query);
                $inserted++;
            }
        } catch (Exception $e) {
            // Error silencioso para líneas mal formadas o duplicados reales
        }
    } else {
        // Ejecutar otras líneas (Terceros, Cuentas, etc.) si son INSERTS completos
        if (strpos($line, "INSERT IGNORE INTO") !== false && substr($line, -1) == ';') {
            try {
                $db->exec($line);
            } catch (Exception $e) {}
        }
    }

    if ($linesProcessed % 5000 == 0) {
        echo "Procesadas $linesProcessed líneas...\n";
    }
}

fclose($handle);
$db->exec("SET foreign_key_checks = 1");

echo "Restauración completada:\n";
echo "- $inserted movimientos restaurados.\n";
echo "- $skippedCxC movimientos de CxC protegidos.\n";
