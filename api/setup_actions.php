<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requireRol('admin');

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$db = Database::getInstance()->getPdo();

try {
    switch ($action) {
        case 'reset_all':
            $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
            
            // Lista completa de tablas a limpiar para un "borrón y cuenta nueva"
            $tables = [
                'asientos_detalles', 'asientos', 'comprobantes', 
                'facturas_detalles', 'facturas', 'recibos', 'egresos',
                'tesoreria_movimientos', 'tesoreria_cuentas',
                'inventario_kardex', 'productos', 'categorias',
                'terceros', 'puc_cuentas', 'centros_costo', 'proyectos',
                'auditoria_logs', 'auditoria_brechas',
                'certificados_retencion', 'cai_resoluciones',
                'contratos_recurrentes', 'depreciaciones'
            ];

            foreach ($tables as $t) {
                $db->exec("TRUNCATE TABLE `$t` ");
            }

            $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
            echo json_encode(['success' => true, 'message' => 'Sistema reseteado a valores de fábrica. Core vacío.']);
            break;

        case 'reset_accounting':
            $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $tables = ['asientos_detalles', 'asientos', 'comprobantes', 'tesoreria_movimientos', 'auditoria_logs'];
            foreach ($tables as $t) { $db->exec("TRUNCATE TABLE `$t` "); }
            $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
            echo json_encode(['success' => true, 'message' => 'Transacciones contables eliminadas. Catálogos preservados.']);
            break;

        case 'import_sql':
            if (!isset($_FILES['file'])) throw new Exception("No se subió ningún archivo.");
            $file = $_FILES['file'];
            if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception("Error en la subida.");
            
            $sql = file_get_contents($file['tmp_name']);
            if (!$sql) throw new Exception("Archivo SQL vacío.");

            $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $db->exec($sql);
            $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
            
            echo json_encode(['success' => true, 'message' => 'Base de Datos MySQL importada con éxito.']);
            break;

        default:
            throw new Exception("Acción no válida.");
    }
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
