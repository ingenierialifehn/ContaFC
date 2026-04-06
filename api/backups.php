<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requireRol('admin');

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$backupDir = __DIR__ . '/../backups';

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

try {
    if ($method === 'GET') {
        if (isset($_GET['settings'])) {
            $pdo = Database::getInstance()->getPdo();
            $pdo->exec("CREATE TABLE IF NOT EXISTS config_backups (
                empresa_id INT NOT NULL,
                frecuencia VARCHAR(20) NOT NULL DEFAULT 'desactivado',
                hora VARCHAR(5) NOT NULL DEFAULT '00:00',
                notificar_email VARCHAR(100) NULL,
                ultimo_backup DATETIME NULL,
                PRIMARY KEY (empresa_id)
            )");
            // Ensure ultimo_backup exists if the table was created recently without it
            try { $pdo->exec("ALTER TABLE config_backups ADD COLUMN ultimo_backup DATETIME NULL"); } catch (Throwable $e) {}
            $stmt = $pdo->prepare("SELECT * FROM config_backups WHERE empresa_id = ?");
            $stmt->execute([Auth::empresaId()]);
            $config = $stmt->fetch();
            echo json_encode(['data' => $config ?: ['frecuencia' => 'desactivado', 'hora' => '00:00']]);
            exit;
        }

        if (isset($_GET['download'])) {
            $file = basename($_GET['download']);
            $path = $backupDir . '/' . $file;
            if (file_exists($path)) {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $file . '"');
                readfile($path);
                exit;
            }
            throw new Exception("Archivo no encontrado.");
        }

        $files = glob($backupDir . '/*.sql');
        $data = array_map(function($f) {
            return [
                'name' => basename($f),
                'size' => round(filesize($f) / 1024 / 1024, 2) . ' MB',
                'date' => date('Y-m-d H:i:s', filemtime($f))
            ];
        }, $files);
        usort($data, fn($a, $b) => strcmp($b['date'], $a['date']));
        echo json_encode(['data' => $data]);
    } 
    elseif ($method === 'POST') {
        if (isset($_GET['settings'])) {
            $input = json_decode(file_get_contents('php://input'), true);
            $pdo = Database::getInstance()->getPdo();
            $pdo->exec("CREATE TABLE IF NOT EXISTS config_backups (
                empresa_id INT NOT NULL,
                frecuencia VARCHAR(20) NOT NULL DEFAULT 'desactivado',
                hora VARCHAR(5) NOT NULL DEFAULT '00:00',
                notificar_email VARCHAR(100) NULL,
                ultimo_backup DATETIME NULL,
                PRIMARY KEY (empresa_id)
            )");
            // Ensure ultimo_backup exists if the table was created recently without it
            try { $pdo->exec("ALTER TABLE config_backups ADD COLUMN ultimo_backup DATETIME NULL"); } catch (Throwable $e) {}
            $stmt = $pdo->prepare("INSERT INTO config_backups (empresa_id, frecuencia, hora, notificar_email) 
                                   VALUES (?, ?, ?, ?)
                                   ON DUPLICATE KEY UPDATE frecuencia = VALUES(frecuencia), hora = VALUES(hora), notificar_email = VALUES(notificar_email)");
            $stmt->execute([
                Auth::empresaId(),
                $input['frecuencia'],
                $input['hora'],
                $input['notificar_email'] ?? null
            ]);
            echo json_encode(['success' => true]);
            exit;
        }

        $dbHost = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: 'contafc_db');
        $dbName = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'contafc');
        $dbUser = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'contafc_user');
        $dbPass = defined('DB_PASSWORD') ? DB_PASSWORD : (getenv('DB_PASSWORD') ?: '');
        
        $filename = 'backup_' . date('Ymd_His') . '.sql';
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

        if ($returnVar !== 0) {
            // Intento 2: Pure PHP dump fallback (útil en Windows sin mysqldump en PATH)
            try {
                $pdo = Database::getInstance()->getPdo();
                $fp = fopen($path, 'w');
                if (!$fp) throw new Exception("No se pudo crear el archivo de respaldo.");
                
                fwrite($fp, "-- Respaldo ContaFC (Fallback PHP)\n");
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
            } catch (Throwable $pe) {
                if (file_exists($path)) @unlink($path);
                $errOut = implode(" ", $output);
                throw new Exception("La utilidad mysqldump falló ($errOut) y el respaldo PHP también: " . $pe->getMessage());
            }
        }

        echo json_encode(['success' => true, 'filename' => $filename]);
    }
    elseif ($method === 'DELETE') {
        $data = json_decode(file_get_contents('php://input'), true);
        $file = basename($data['filename'] ?? '');
        $path = $backupDir . '/' . $file;
        if (file_exists($path)) {
            unlink($path);
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Archivo no existe.");
        }
    }
} catch (Throwable $e) {
    // Usamos 400 en vez de 500 para evitar que el servidor web lo intercepte 
    // devolviendo el ErrorDocument en HTML que causa error JSON en el cliente
    http_response_code(400); 
    echo json_encode(['error' => $e->getMessage()]);
}
