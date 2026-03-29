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
        $dbHost = $_ENV['DB_HOST'] ?? 'db';
        $dbName = $_ENV['DB_NAME'] ?? 'contafc';
        $dbUser = $_ENV['DB_USER'] ?? 'contafc_user';
        $dbPass = $_ENV['DB_PASSWORD'] ?? '';
        
        $filename = 'backup_' . date('Ymd_His') . '.sql';
        $path = $backupDir . '/' . $filename;
        
        // Comando mysqldump para Docker (el mysql-client debe estar en el contenedor PHP)
        $cmd = sprintf(
            'mysqldump -h %s -u %s --password=%s %s > %s 2>&1',
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbName),
            escapeshellarg($path)
        );

        exec($cmd, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new Exception("Error al generar backup: " . implode("\n", $output));
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
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
