<?php
// Script de Restauración Inteligente V2.1 (Mapeo de IDs + Protección CxC)
require_once __DIR__ . '/../bootstrap.php';
$pdo = \ContaFC\Core\Database::getInstance()->getPdo();

$backupPath = __DIR__ . '/../backups/backup_20260406_104035.sql';
if (!file_exists($backupPath)) {
    die("Error: No se encontró el respaldo en $backupPath\n");
}

echo "Fase 1: Cargando mapa de cuentas actuales...\n";
$currentCuentas = [];
$stmt = $pdo->query("SELECT id, codigo FROM puc_cuentas");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $currentCuentas[$row['codigo']] = $row['id'];
}

echo "Fase 2: Procesando respaldo para mapear IDs viejos...\n";
$backupIdToCodigo = [];
$handle = fopen($backupPath, "r");
while (($line = fgets($handle)) !== false) {
    if (strpos($line, "INSERT INTO `puc_cuentas` VALUES") !== false) {
        $partNode = strpos($line, "VALUES ");
        $valuesStr = substr($line, $partNode + 7);
        $records = explode("),", $valuesStr);
        foreach ($records as $record) {
            $record = trim($record, " ()\r\n;");
            $fields = str_getcsv($record, ",", "'");
            if (count($fields) >= 3) {
                $backupIdToCodigo[$fields[0]] = $fields[2];
            }
        }
    }
}
fclose($handle);

echo "Fase 3: Restaurando asientos filtrados...\n";
$pdo->exec("SET foreign_key_checks = 0");
// Limpiar asientos previos de migración (excepto los de clientes que queremos proteger)
$pdo->exec("DELETE FROM asientos WHERE comprobante_id = 9999 AND cuenta_id NOT IN (SELECT id FROM puc_cuentas WHERE codigo IN ('11050101', '110301'))");

$handle = fopen($backupPath, "r");
$inserted = 0;
$skipped = 0;
$linesCount = 0;

while (($line = fgets($handle)) !== false) {
    $linesCount++;
    if (strpos($line, "INSERT INTO `asientos` VALUES") !== false) {
        $partNode = strpos($line, "VALUES ");
        $valuesStr = substr($line, $partNode + 7);
        // Dividir multi-inserts manualmente por ),( pero considerando escapes (simplificado)
        $records = explode("),", $valuesStr);
        foreach ($records as $record) {
            $record = trim($record, " ()\r\n;");
            $fields = str_getcsv($record, ",", "'");
            if (count($fields) < 10) continue;

            $oldCuentaId = $fields[4];
            $codigo = $backupIdToCodigo[$oldCuentaId] ?? null;
            
            // PROTECCIÓN: Saltar si es cuenta de clientes
            if ($codigo == '11050101' || $codigo == '110301') {
                $skipped++;
                continue;
            }

            // Obtener ID actual de la cuenta
            $newCuentaId = $currentCuentas[$codigo] ?? null;
            if (!$newCuentaId) continue;

            $debito = $fields[8] == 'NULL' ? 0 : (float)$fields[8];
            $credito = $fields[9] == 'NULL' ? 0 : (float)$fields[9];
            $desc = $fields[10] == 'NULL' ? '' : $fields[10];
            $fecha = count($fields) > 26 ? $fields[26] : '2023-01-01';
            
            try {
                $insertStmt = $pdo->prepare("INSERT IGNORE INTO asientos 
                    (empresa_id, comprobante_id, linea, cuenta_id, debito, credito, descripcion, fecha) 
                    VALUES (1, 9999, ?, ?, ?, ?, ?, ?)");
                $insertStmt->execute([
                    $inserted + 1, // nueva linea
                    $newCuentaId,
                    $debito,
                    $credito,
                    $desc,
                    $fecha
                ]);
                $inserted++;
            } catch (Exception $e) {}
        }
    }
    if ($linesCount % 5000 == 0) echo "Procesadas $linesCount líneas...\n";
}

fclose($handle);
$pdo->exec("SET foreign_key_checks = 1");

echo "\nResultado:\n";
echo "- $inserted movimientos restaurados (Bancos, Inventarios, etc).\n";
echo "- $skipped movimientos de Clientes protegidos.\n";
