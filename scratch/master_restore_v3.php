<?php
// master_restore_v3.php - Restauración robusta de movimientos contables
require_once __DIR__ . '/../bootstrap.php';
$pdo = \ContaFC\Core\Database::getInstance()->getPdo();

$backupPath = __DIR__ . '/../backups/backup_20260406_104035.sql';
if (!file_exists($backupPath)) {
    die("Error: No se encontró el respaldo en $backupPath\n");
}

echo "--- FASE 1: Preparando mapas locales ---\n";
// Mapa Cuentas: Codigo -> ID
$cuentasMap = [];
$stmt = $pdo->query("SELECT id, codigo FROM puc_cuentas");
while ($row = $stmt->fetch()) $cuentasMap[$row['codigo']] = $row['id'];

// Mapa Terceros: NIT -> ID (Usamos nit_cc)
$tercerosMap = [];
$stmt = $pdo->query("SELECT id, nit_cc FROM terceros");
while ($row = $stmt->fetch()) {
    $nit = trim((string)$row['nit_cc']);
    if ($nit !== '') $tercerosMap[$nit] = $row['id'];
}

// Mapa Conteos: Conteo -> true (para evitar duplicados exactos de migración)
$existingConteos = [];
$stmt = $pdo->query("SELECT conteo FROM asientos WHERE conteo IS NOT NULL");
while ($row = $stmt->fetch()) $existingConteos[(int)$row['conteo']] = true;

echo "--- FASE 2: Mapeando IDs de Cuentas del respaldo ---\n";
$backupIdToCodigo = [];
$handle = fopen($backupPath, "r");
while (($line = fgets($handle)) !== false) {
    if (strpos($line, "INSERT INTO `puc_cuentas` VALUES") !== false) {
        $part = substr($line, strpos($line, "VALUES ") + 7);
        $records = explode("),", $part);
        foreach ($records as $r) {
            $f = str_getcsv(trim($r, " ()\r\n;"), ",", "'");
            if (count($f) >= 3) {
                $backupIdToCodigo[$f[0]] = $f[2]; // ID -> Codigo
            }
        }
    }
}
fclose($handle);
echo "Mapped " . count($backupIdToCodigo) . " accounts from backup.\n";

echo "--- FASE 3: Restaurando Movimientos (Excluyendo CxC) ---\n";
$pdo->exec("SET foreign_key_checks = 0");

$handle = fopen($backupPath, "r");
$inserted = 0;
$skipped_cxc = 0;
$skipped_dup = 0;
$skipped_no_acct = 0;
$skipped_short = 0;
$skipped_sql_error = 0;
$lineNum = 0;

$stmt = $pdo->query("SELECT COALESCE(MAX(linea), 0) FROM asientos WHERE comprobante_id = 9999");
$nextLinea = (int)$stmt->fetchColumn() + 1;
echo "Starting from next available linea: $nextLinea\n";

$protected_codes = ['11050101', '110301', '11020101'];

while (($line = fgets($handle)) !== false) {
    $lineNum++;
    if (strpos($line, "INSERT INTO `asientos` VALUES") !== false) {
        $part = substr($line, strpos($line, "VALUES ") + 7);
        // Dividir multi-inserts (asumiendo que no hay ), dentro de strings, 
        // lo cual es arriesgado pero común en dumps simples)
        $records = explode("),", $part);
        foreach ($records as $r) {
            $clean_r = trim($r, " ()\r\n;");
            if (empty($clean_r)) continue;
            
            $f = str_getcsv($clean_r, ",", "'");
            if (count($f) < 27) {
                $skipped_short++;
                continue;
            }

            $oldCuentaId = $f[4];
            $codigo = $backupIdToCodigo[$oldCuentaId] ?? null;
            $conteo = ($f[25] === 'NULL' || $f[25] === null) ? null : (int)$f[25];

            // 1. PROTECCIÓN CxC: No tocar lo que el usuario está conciliando
            if (in_array($codigo, $protected_codes)) {
                $skipped_cxc++;
                continue;
            }

            // 2. EVITAR DUPLICADOS: Si ya existe este conteo, lo saltamos
            if ($conteo !== null && isset($existingConteos[$conteo])) {
                $skipped_dup++;
                continue;
            }

            // 3. MAPEO DE ENTIDADES
            $newCuentaId = $cuentasMap[$codigo] ?? null;
            if (!$newCuentaId) {
                $skipped_no_acct++;
                continue;
            }

            $nit = trim($f[13] ?? '');
            $newTerceroId = (!empty($nit) && $nit !== 'NULL') ? ($tercerosMap[$nit] ?? null) : null;

            // 4. PREPARACIÓN DE VALORES
            $comprobante_id = 9999; // Forzamos a lote de migración para control
            $empresa_id = 1;
            $linea = $nextLinea++;
            $debito = (float)$f[8];
            $credito = (float)$f[9];
            $desc = $f[10] === 'NULL' ? '' : $f[10];
            $fecha = $f[26] === 'NULL' ? '2023-01-01' : $f[26];
            
            // Documentos de cruce si existen
            $doc_tipo = ($f[15] === 'NULL') ? null : $f[15];
            $doc_num  = ($f[16] === 'NULL') ? null : $f[16];
            $doc_cuota = ($f[18] === 'NULL') ? null : (int)$f[18];
            $vencimiento = ($f[19] === 'NULL') ? null : $f[19];

            // EJECUCIÓN DEL INSERT
            try {
                $sql = "INSERT INTO asientos 
                    (empresa_id, comprobante_id, linea, fecha, cuenta_id, tercero_id, debito, credito, descripcion, 
                     doc_cruce_tipo, doc_cruce_num, doc_cruce_cuota, vencimiento, conteo) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $ins = $pdo->prepare($sql);
                $ins->execute([
                    $empresa_id, $comprobante_id, $linea, $fecha, $newCuentaId, $newTerceroId,
                    $debito, $credito, $desc, $doc_tipo, $doc_num, $doc_cuota, $vencimiento, $conteo
                ]);
                $inserted++;
                
                // Agregamos al mapa de conteos para evitar duplicados en el mismo proceso si el SQL vino raro
                if ($conteo !== null) $existingConteos[$conteo] = true;
                
            } catch (Exception $e) {
                $skipped_sql_error++;
                // echo "Error en linea $lineNum: " . $e->getMessage() . "\n";
            }
        }
    }
    if ($lineNum % 5000 == 0) echo "Procesadas $lineNum líneas del backup...\n";
}

fclose($handle);
$pdo->exec("SET foreign_key_checks = 1");

echo "\n--- RESULTADO FINAL ---\n";
echo "Total de movimientos insertados: $inserted\n";
echo "Movimientos de cuentas protegidas (omitidos): $skipped_cxc\n";
echo "Registros ya existentes (omitidos por duplicidad): $skipped_dup\n";
echo "Registros con cuenta no existente (omitidos): $skipped_no_acct\n";
echo "Registros cortos o mal formateados (omitidos): $skipped_short\n";
echo "Registros omitidos por error SQL: $skipped_sql_error\n";
echo "----------------------------------------\n";
