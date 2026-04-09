<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

// Auth sin redirect HTML
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

// Módulos disponibles en el sistema
$MODULOS_MASTER = [
    'ecosistema_comercial' => [
        'nombre'      => 'Ecosistema Comercial',
        'descripcion' => 'POS, Facturación SAR, Inventario, Logística, Contratos, Notas de Crédito.',
        'icono'       => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z',
        'color'       => '#f59e0b',
    ],
    'contabilidad_core' => [
        'nombre'      => 'Contabilidad Core',
        'descripcion' => 'Asientos de Diario, Comprobantes, Activos Fijos, Libros Oficiales, Plan de Cuentas, Terceros.',
        'icono'       => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
        'color'       => '#0ea5e9',
    ],
    'cartera_cobros' => [
        'nombre'      => 'Cartera y Cobros',
        'descripcion' => 'Gestión de créditos, recaudos y seguimiento de cartera.',
        'icono'       => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        'color'       => '#10b981',
    ],
    'reportes' => [
        'nombre'      => 'Reportes y Balances',
        'descripcion' => 'Reportes financieros, balances, estados de resultados.',
        'icono'       => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        'color'       => '#8b5cf6',
    ],
    'tesoreria' => [
        'nombre'      => 'Tesoreria',
        'descripcion' => 'Bancos, tesoreria y egresos recurrentes.',
        'icono'       => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
        'color'       => '#ec4899',
    ],
];

try {
    // Todo dentro del try/catch para garantizar respuesta JSON siempre
    $db        = Database::getInstance()->getPdo();
    $user      = Auth::user();
    $method    = $_SERVER['REQUEST_METHOD'];
    $empresaId = Auth::empresaId();

    // ── GET: Leer módulos activos ─────────────────────────────────
    if ($method === 'GET') {
        $modulosActivos = null;

        try {
            $col = $db->query("SHOW COLUMNS FROM empresas LIKE 'modulos_activos'");
            if ($col && $col->rowCount() > 0) {
                $stmt = $db->prepare("SELECT modulos_activos FROM empresas WHERE id = :id LIMIT 1");
                $stmt->execute([':id' => $empresaId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && !empty($row['modulos_activos'])) {
                    $dec = json_decode($row['modulos_activos'], true);
                    if (is_array($dec)) $modulosActivos = $dec;
                }
            }
        } catch (\Throwable $innerEx) {
            // Columna no existe aún — devolver defaults
        }

        // Default: todos activos
        if ($modulosActivos === null) {
            $modulosActivos = [];
            foreach ($MODULOS_MASTER as $k => $_v) {
                $modulosActivos[$k] = true;
            }
        }

        $out = [];
        foreach ($MODULOS_MASTER as $key => $meta) {
            $out[] = [
                'key'         => $key,
                'nombre'      => $meta['nombre'],
                'descripcion' => $meta['descripcion'],
                'icono'       => $meta['icono'],
                'color'       => $meta['color'],
                'activo'      => (bool)($modulosActivos[$key] ?? true),
            ];
        }

        echo json_encode(['success' => true, 'data' => $out]);
    }

    // ── POST: Guardar módulos ─────────────────────────────────────
    elseif ($method === 'POST') {
        if (($user['rol'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Solo administradores pueden modificar módulos.']);
            exit;
        }

        $body = json_decode(file_get_contents('php://input'), true);
        if (!isset($body['modulos']) || !is_array($body['modulos'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Payload invalido.']);
            exit;
        }

        $vals = [];
        foreach ($MODULOS_MASTER as $key => $_v) {
            $vals[$key] = !empty($body['modulos'][$key]);
        }

        // Crear columna si no existe
        try {
            $c2 = $db->query("SHOW COLUMNS FROM empresas LIKE 'modulos_activos'");
            if ($c2 && $c2->rowCount() === 0) {
                $db->exec("ALTER TABLE empresas ADD COLUMN modulos_activos JSON NULL");
            }
        } catch (\Throwable $ign) { }

        $s = $db->prepare("UPDATE empresas SET modulos_activos = :m WHERE id = :id");
        $s->execute([':m' => json_encode($vals), ':id' => $empresaId]);

        echo json_encode(['success' => true, 'message' => 'Modulos actualizados correctamente.']);
    }

    else {
        http_response_code(405);
        echo json_encode(['error' => 'Metodo no permitido.']);
    }

} catch (\Throwable $e) {
    http_response_code(500);
    // Incluir causa raíz para diagnóstico
    $prev = $e->getPrevious();
    echo json_encode([
        'error' => $e->getMessage(),
        'causa' => $prev ? $prev->getMessage() : null,
        'linea' => $e->getLine(),
        'archivo' => basename($e->getFile()),
    ]);
}
