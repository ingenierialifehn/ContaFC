<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

header('Content-Type: application/json; charset=utf-8');
Auth::requireAuth();

$db  = Database::getInstance()->getPdo();
$user = Auth::user();
$method = $_SERVER['REQUEST_METHOD'];
$isMultipart = str_contains(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'multipart/form-data');

try {
    if ($method === 'GET') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if ($id) {
            if ($user['rol'] !== 'admin') {
                $check = $db->prepare("SELECT 1 FROM usuarios_empresas WHERE usuario_id = :uid AND empresa_id = :eid");
                $check->execute([':uid' => $user['id'], ':eid' => $id]);
                if (!$check->fetch()) throw new \RuntimeException('No tiene acceso a esta empresa.');
            }
            $stmt = $db->prepare("SELECT * FROM empresas WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['data' => $stmt->fetch()]);
        } else {
            if ($user['rol'] === 'admin') {
                $stmt = $db->query("SELECT * FROM empresas ORDER BY nombre ASC");
                echo json_encode(['data' => $stmt->fetchAll()]);
            } else {
                $stmt = $db->prepare(
                    "SELECT e.* FROM empresas e 
                     JOIN usuarios_empresas ue ON e.id = ue.empresa_id 
                     WHERE ue.usuario_id = :uid AND e.activa = 1 ORDER BY e.nombre ASC"
                );
                $stmt->execute([':uid' => $user['id']]);
                echo json_encode(['data' => $stmt->fetchAll()]);
            }
        }
    } 
    elseif ($method === 'POST') {
        $body = getRequestData();
        if (!$body) throw new \RuntimeException('Payload inválido.');

        // Acción especial: Seleccionar empresa para la sesión
        if (isset($body['action']) && $body['action'] === 'select') {
            $eid = (int)$body['id'];
            // Verificar acceso
            if ($user['rol'] !== 'admin') {
                $check = $db->prepare("SELECT 1 FROM usuarios_empresas WHERE usuario_id = :uid AND empresa_id = :eid");
                $check->execute([':uid' => $user['id'], ':eid' => $eid]);
                if (!$check->fetch()) throw new \RuntimeException('No tiene acceso a esta empresa.');
            }
            $_SESSION['empresa'] = $eid;
            echo json_encode(['success' => true]);
            exit;
        }

        Auth::requireRol('admin');

        $requestedMethod = strtoupper((string)($body['_method'] ?? 'POST'));
        $id      = !empty($body['id']) ? (int)$body['id'] : null;
        $codigo  = trim($body['codigo']);
        $nombre  = trim($body['nombre']);
        $nit     = trim($body['nit'] ?? '');
        $dir     = trim($body['direccion'] ?? '');
        $tel     = trim($body['telefono'] ?? '');
        $ciu     = trim($body['ciudad'] ?? '');
        $dep     = trim($body['departamento'] ?? '');
        $moneda  = trim($body['moneda_base'] ?? 'HNL');
        $activa  = (int)($body['activa'] ?? 1);
        $removeLogo = (int)($body['remove_logo'] ?? 0) === 1;

        if (!$codigo || !$nombre) throw new \RuntimeException('Código y Nombre son obligatorios.');

        if ($requestedMethod === 'PUT' || $id) {
            if (!$id) throw new \RuntimeException('ID faltante.');

            $stmtCurrent = $db->prepare("SELECT logo_path FROM empresas WHERE id = :id");
            $stmtCurrent->execute([':id' => $id]);
            $current = $stmtCurrent->fetch();
            if (!$current) throw new \RuntimeException('Empresa no encontrada.');

            $logoPath = $current['logo_path'] ?? null;
            if ($removeLogo && $logoPath) {
                deleteUploadedFile((string)$logoPath);
                $logoPath = null;
            }
            if ($isMultipart && isset($_FILES['logo']) && (int)($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $newLogoPath = storeUploadedLogo($_FILES['logo'], $codigo);
                if ($logoPath && $logoPath !== $newLogoPath) {
                    deleteUploadedFile((string)$logoPath);
                }
                $logoPath = $newLogoPath;
            }

            $stmt = $db->prepare(
                "UPDATE empresas SET 
                        codigo = :c, nombre = :n, nit = :nit, direccion = :d, telefono = :t, 
                        ciudad = :ci, departamento = :dep, moneda_base = :m, activa = :a, logo_path = :logo
                 WHERE id = :id"
            );
            $stmt->execute([':c'=>$codigo, ':n'=>$nombre, ':nit'=>$nit, ':d'=>$dir, ':t'=>$tel, ':ci'=>$ciu, ':dep'=>$dep, ':m'=>$moneda, ':a'=>$activa, ':logo'=>$logoPath, ':id'=>$id]);
        } else {
            $db->beginTransaction();

            $logoPath = null;
            if ($isMultipart && isset($_FILES['logo']) && (int)($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $logoPath = storeUploadedLogo($_FILES['logo'], $codigo);
            }

            $stmt = $db->prepare(
                "INSERT INTO empresas (codigo, nombre, nit, direccion, telefono, ciudad, departamento, moneda_base, activa, logo_path)
                 VALUES (:c, :n, :nit, :d, :t, :ci, :dep, :m, :a, :logo)"
            );
            $stmt->execute([':c'=>$codigo, ':n'=>$nombre, ':nit'=>$nit, ':d'=>$dir, ':t'=>$tel, ':ci'=>$ciu, ':dep'=>$dep, ':m'=>$moneda, ':a'=>$activa, ':logo'=>$logoPath]);
            $newId = (int)$db->lastInsertId();

            // Vincular al admin actual
            $stmtUE = $db->prepare("INSERT IGNORE INTO usuarios_empresas (usuario_id, empresa_id) VALUES (:uid, :eid)");
            $stmtUE->execute([':uid' => $user['id'], ':eid' => $newId]);

            // Inicializar PUC de Honduras
            inicializarPUCHonduras($db, $newId);

            $db->commit();
        }
        echo json_encode(['success' => true]);
    }
    elseif ($method === 'PUT') {
        Auth::requireRol('admin');
        $body = getRequestData();
        $id = (int)($body['id'] ?? 0);
        if (!$id) throw new \RuntimeException('ID faltante.');
        
        $stmt = $db->prepare(
            "UPDATE empresas SET 
                    codigo = :c, nombre = :n, nit = :nit, direccion = :d, telefono = :t, 
                    ciudad = :ci, departamento = :dep, moneda_base = :m, activa = :a
             WHERE id = :id"
        );
        $stmt->execute([
            ':c' => trim($body['codigo']),
            ':n' => trim($body['nombre']),
            ':nit' => trim($body['nit'] ?? ''),
            ':d' => trim($body['direccion'] ?? ''),
            ':t' => trim($body['telefono'] ?? ''),
            ':ci' => trim($body['ciudad'] ?? ''),
            ':dep' => trim($body['departamento'] ?? ''),
            ':m' => trim($body['moneda_base'] ?? 'HNL'),
            ':a' => (int)($body['activa'] ?? 1),
            ':id' => $id
        ]);
        echo json_encode(['success' => true]);
    }
    elseif ($method === 'DELETE') {
        Auth::requireRol('admin');
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) throw new \RuntimeException('ID inválido.');
        if ($id === 1) throw new \RuntimeException('La empresa principal no se puede eliminar.');

        $stmtLogo = $db->prepare("SELECT logo_path FROM empresas WHERE id = :id");
        $stmtLogo->execute([':id' => $id]);
        $logoPath = $stmtLogo->fetchColumn();
        if ($logoPath) {
            deleteUploadedFile((string)$logoPath);
        }

        $db->prepare("DELETE FROM empresas WHERE id = :id")->execute([':id' => $id]);
        echo json_encode(['success' => true]);
    }
} catch (\Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function inicializarPUCHonduras($db, $eid) {
    $puc = [
        ['1',      'ACTIVO',                          1, null,   'D', 'A', 0],
        ['11',     'ACTIVO CORRIENTE',                2, '1',    'D', 'A', 0],
        ['1101',   'EFECTIVO Y EQUIVALENTES',         3, '11',   'D', 'A', 0],
        ['110101', 'Caja General',                    4, '1101', 'D', 'A', 1],
        ['110102', 'Bancos - Cuentas de Cheques',     4, '1101', 'D', 'A', 1],
        ['1103',   'CUENTAS POR COBRAR',              3, '11',   'D', 'A', 0],
        ['110301', 'Clientes Nacionales',             4, '1103', 'D', 'A', 1],
        ['1104',   'INVENTARIOS',                     3, '11',   'D', 'A', 0],
        ['110401', 'Mercaderías',                     4, '1104', 'D', 'A', 1],
        ['2',      'PASIVO',                          1, null,   'C', 'P', 0],
        ['21',     'PASIVO CORRIENTE',                2, '2',    'C', 'P', 0],
        ['2101',   'CUENTAS Y DOC. POR PAGAR',        3, '21',   'C', 'P', 0],
        ['210101', 'Proveedores Nacionales',          4, '2101', 'C', 'P', 1],
        ['3',      'PATRIMONIO NETO',                 1, null,   'C', 'R', 0],
        ['31',     'CAPITAL',                         2, '3',    'C', 'R', 0],
        ['3101',   'Capital Social',                  3, '31',   'C', 'R', 1],
        ['4',      'INGRESOS',                        1, null,   'C', 'R', 0],
        ['41',     'INGRESOS OPERATIVOS',             2, '4',    'C', 'R', 0],
        ['4101',   'Ventas de Mercaderías',           3, '41',   'C', 'R', 1],
        ['5',      'GASTOS',                          1, null,   'D', 'G', 0],
        ['51',     'GASTOS DE OPERACIÓN',             2, '5',    'D', 'G', 0],
        ['5102',   'Gastos de Administración',        3, '51',   'D', 'G', 0],
        ['510201', 'Sueldos y Salarios',              4, '5102', 'D', 'G', 1],
        ['6',      'COSTOS',                          1, null,   'D', 'G', 0],
        ['61',     'COSTO DE VENTAS',                 2, '6',    'D', 'G', 1],
    ];

    $stmt = $db->prepare(
        "INSERT INTO puc_cuentas (empresa_id, codigo, nombre, nivel, codigo_padre, naturaleza, tipo_cuenta, acepta_movimiento)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    foreach ($puc as $row) {
        $stmt->execute([$eid, $row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6]]);
    }
}

function getRequestData(): array {
    if (!empty($_POST)) {
        return $_POST;
    }

    $body = json_decode(file_get_contents('php://input'), true);
    return is_array($body) ? $body : [];
}

function storeUploadedLogo(array $file, string $empresaCodigo): string {
    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        throw new \RuntimeException('No se pudo cargar la imagen de la empresa.');
    }

    $tmpName = $file['tmp_name'] ?? '';
    if (!is_uploaded_file($tmpName)) {
        throw new \RuntimeException('Archivo de imagen invÃ¡lido.');
    }

    $mime = mime_content_type($tmpName) ?: '';
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    if (!isset($allowed[$mime])) {
        throw new \RuntimeException('Formato de imagen no permitido. Use JPG, PNG, WEBP o GIF.');
    }

    $uploadDir = __DIR__ . '/../uploads/empresas';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        throw new \RuntimeException('No se pudo crear la carpeta de logos.');
    }

    $safeCode = preg_replace('/[^A-Za-z0-9_-]/', '_', $empresaCodigo) ?: 'empresa';
    $filename = $safeCode . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $target = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($tmpName, $target)) {
        throw new \RuntimeException('No se pudo guardar la imagen de la empresa.');
    }

    return 'uploads/empresas/' . $filename;
}

function deleteUploadedFile(string $relativePath): void {
    $normalized = ltrim(str_replace(['\\', '..'], ['/', ''], $relativePath), '/');
    $root = realpath(__DIR__ . '/../');
    if ($root === false) {
        return;
    }

    $fullPath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}
