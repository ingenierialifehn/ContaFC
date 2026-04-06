<?php
/**
 * scripts/auto_backup.php
 * Script para automatización de respaldos.
 * Debe ejecutarse vía CLI (Cron Job o Task Scheduler).
 */
declare(strict_types=1);

// Cargar variables de entorno si existen (o definir DB_HOST manualmente si falla bootstrap)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../src/Core/Database.php';

use ContaFC\Core\Database;

echo "[" . date('Y-m-d H:i:s') . "] Iniciando verificación de backups automáticos...\n";

try {
    $pdo = Database::getInstance()->getPdo();
    
    // Obtener empresas con backup activado
    $stmt = $pdo->query("SELECT c.*, e.nombre as empresa_nombre 
                         FROM config_backups c 
                         JOIN empresas e ON c.empresa_id = e.id 
                         WHERE c.frecuencia != 'desactivado'");
    $configs = $stmt->fetchAll();

    if (empty($configs)) {
        echo "No hay backups automáticos configurados.\n";
        exit;
    }

    $backupDir = __DIR__ . '/../backups';
    if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

    foreach ($configs as $cfg) {
        if (!shouldRunBackup($cfg)) {
            echo "Skipping {$cfg['empresa_nombre']} (No es momento de ejecución).\n";
            continue;
        }

        echo "Ejecutando backup para: {$cfg['empresa_nombre']}...\n";

        $dbHost = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: 'contafc_db');
        $dbName = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'contafc');
        $dbUser = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'contafc_user');
        $dbPass = defined('DB_PASSWORD') ? DB_PASSWORD : (getenv('DB_PASSWORD') ?: '');

        $filename = 'auto_' . strtolower(str_replace(' ', '_', $cfg['empresa_nombre'])) . '_' . date('Ymd_His') . '.sql';
        $path = $backupDir . '/' . $filename;

        $mysqldumpPath = 'mysqldump';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (file_exists('C:\\xampp\\mysql\\bin\\mysqldump.exe')) {
                $mysqldumpPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
            }
        }

        $cmd = sprintf(
            '%s -h %s -u %s --password=%s %s > %s 2>&1',
            escapeshellarg($mysqldumpPath),
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbName),
            escapeshellarg($path)
        );

        exec($cmd, $output, $returnVar);

        $success = false;

        if ($returnVar !== 0) {
            // Intento 2: Pure PHP dump fallback (útil en Windows sin mysqldump en PATH)
            try {
                $fp = fopen($path, 'w');
                if (!$fp) throw new Exception("No se pudo crear el archivo de respaldo.");
                
                fwrite($fp, "-- Respaldo ContaFC Auto (Fallback PHP)\n");
                fwrite($fp, "-- Fecha: " . date('Y-m-d H:i:s') . "\n\n");
                fwrite($fp, "SET FOREIGN_KEY_CHECKS=0;\n\n");
                
                $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
                foreach ($tables as $table) {
                    $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(\PDO::FETCH_ASSOC);
                    fwrite($fp, "DROP TABLE IF EXISTS `$table`;\n");
                    fwrite($fp, $create['Create Table'] . ";\n\n");
                    
                    $rows = $pdo->query("SELECT * FROM `$table`");
                    while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                        $vals = array_map(function($val) use ($pdo) {
                            return $val === null ? 'NULL' : $pdo->quote((string)$val);
                        }, array_values($row));
                        fwrite($fp, "INSERT INTO `$table` VALUES (" . implode(',', $vals) . ");\n");
                    }
                    fwrite($fp, "\n");
                }
                fwrite($fp, "SET FOREIGN_KEY_CHECKS=1;\n");
                fclose($fp);
                $success = true;
            } catch (Throwable $pe) {
                if (file_exists($path)) @unlink($path);
                echo "ERROR en backup (mysqldump y fallback fallaron): " . $pe->getMessage() . "\n";
            }
        } else {
            $success = true;
        }

        if ($success) {
            echo "Backup exitoso: {$filename}\n";
            // Actualizar fecha de último backup
            $upd = $pdo->prepare("UPDATE config_backups SET ultimo_backup = NOW() WHERE empresa_id = ?");
            $upd->execute([$cfg['empresa_id']]);
        }
    }

} catch (Throwable $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
}

/**
 * Lógica para determinar si el backup debe correr hoy
 */
function shouldRunBackup($cfg): bool {
    $now = new DateTime();
    $last = $cfg['ultimo_backup'] ? new DateTime($cfg['ultimo_backup']) : null;
    $targetTime = $cfg['hora']; // HH:mm:ss
    
    // Si la hora actual es menor a la hora configurada, no correr hoy todavía
    if ($now->format('H:i') < substr($targetTime, 0, 5)) return false;

    if (!$last) return true; // Nunca se ha hecho

    $diff = $now->diff($last);

    switch ($cfg['frecuencia']) {
        case 'diaria':
            return $diff->days >= 1;
        case 'semanal':
            return $diff->days >= 7;
        case 'mensual':
            return $diff->days >= 30;
    }

    return false;
}
