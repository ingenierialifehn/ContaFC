<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();

header('Content-Type: application/json');

$db  = Database::getInstance()->getPdo();
$eid = Auth::empresaId();
$uid = Auth::userId();
$method = $_SERVER['REQUEST_METHOD'];
$asientosTieneFecha = tableHasColumn($db, 'asientos', 'fecha');

try {
    if ($method === 'POST') {
        Auth::requirePermiso('asiento', 'c');
        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body || empty($body['lineas'])) throw new Exception("Datos incompletos.");

        $tDeb = 0; $tCre = 0;
        foreach ($body['lineas'] as $l) {
            $tDeb += (float)$l['debito'];
            $tCre += (float)$l['credito'];
        }
        if (abs($tDeb - $tCre) > 0.01) {
            throw new Exception("El asiento no está balanceado. Débitos: $tDeb, Créditos: $tCre");
        }

        $db->beginTransaction();

        // 1. Crear el Comprobante
        $stmtComp = $db->prepare(
            "INSERT INTO comprobantes (empresa_id, tipo_comp_id, numero, fecha, tercero_id, observaciones, periodo_id, usuario_id, estado)
             VALUES (:eid, :tid, :num, :fec, :ter, :obs, :pid, :uid, 'registrado')"
        );
        
        // Generar número automático (ejemplo simple: max+1 por tipo y empresa)
        $numStmt = $db->prepare("SELECT COALESCE(MAX(numero), 0) + 1 FROM comprobantes WHERE empresa_id = :eid AND tipo_comp_id = :tid");
        $numStmt->execute([':eid' => $eid, ':tid' => $body['tipo_comp_id']]);
        $numero = $numStmt->fetchColumn();

        // Buscar periodo activo para la fecha
        $periodoStmt = $db->prepare("SELECT id FROM periodos WHERE empresa_id = :eid AND anio = :a AND mes = :m LIMIT 1");
        $periodoStmt->execute([':eid' => $eid, ':a' => date('Y', strtotime($body['fecha'])), ':m' => (int)date('m', strtotime($body['fecha']))]);
        $pid = $periodoStmt->fetchColumn() ?: 1;

        $stmtComp->execute([
            ':eid' => $eid,
            ':tid' => $body['tipo_comp_id'],
            ':num' => $numero,
            ':fec' => $body['fecha'],
            ':pid' => $pid,
            ':ter' => $body['tercero_id'] ?: null,
            ':obs' => $body['observaciones'] ?: null,
            ':uid' => $uid
        ]);
        $compId = $db->lastInsertId();

        // 2. Insertar Líneas de Asiento
        $stmtLinea = $db->prepare(
            "INSERT INTO asientos (comprobante_id, empresa_id, linea, cuenta_id, debito, credito, tercero_id, descripcion, doc_cruce_tipo, doc_cruce_num, fecha)
             VALUES (:cid, :eid, :lin, :cta, :deb, :cre, :ter, :des, :dct, :dcn, :fec)"
        );

        $lineaIdx = 1;
        foreach ($body['lineas'] as $l) {
            $stmtLinea->execute([
                ':cid' => $compId,
                ':eid' => $eid,
                ':lin' => $lineaIdx++,
                ':cta' => $l['cuenta_id'],
                ':deb' => $l['debito'],
                ':cre' => $l['credito'],
                ':ter' => $l['tercero_id'] ?: null,
                ':des' => $l['descripcion'] ?: null,
                ':dct' => $l['doc_cruce_tipo'] ?? null,
                ':dcn' => $l['doc_cruce_num'] ?? null,
                ':fec' => $l['fecha'] ?? $body['fecha']
            ]);
            
            // 4. Actualizar Saldos de Cuenta (Optimización para reportes)
            actualizarSaldoCuenta($db, $eid, $pid, (int)$l['cuenta_id'], (float)$l['debito'], (float)$l['credito']);
        }

        $db->commit();
        echo json_encode(['success' => true, 'comprobante_id' => $compId, 'numero' => $numero]);
    } 
    elseif ($method === 'GET') {
        Auth::requirePermiso('comprobantes', 'r');
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if ($id) {
            $fechaOperativaSql = $asientosTieneFecha
                ? "COALESCE((SELECT MAX(a.fecha) FROM asientos a WHERE a.comprobante_id = c.id AND a.fecha IS NOT NULL), c.fecha) AS fecha_operativa,"
                : "c.fecha AS fecha_operativa,";
            $stmt = $db->prepare(
                "SELECT c.*, {$fechaOperativaSql} t.razon_social as tercero_nombre, tc.nombre as tipo_nombre, tc.codigo as tipo_codigo, u.username as usuario_registro
                 FROM comprobantes c
                 LEFT JOIN terceros t ON c.tercero_id = t.id
                 JOIN tipos_comprobante tc ON c.tipo_comp_id = tc.id
                 LEFT JOIN usuarios u ON c.usuario_id = u.id
                 WHERE c.id = :id AND c.empresa_id = :eid"
            );
            $stmt->execute([':id' => $id, ':eid' => $eid]);
            $comp = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$comp) throw new Exception("Comprobante no encontrado.");

            $stmtL = $db->prepare(
                "SELECT a.*, cu.codigo as cuenta_codigo, cu.nombre as cuenta_nombre, t.razon_social as tercero_nombre
                 FROM asientos a
                 JOIN puc_cuentas cu ON a.cuenta_id = cu.id
                 LEFT JOIN terceros t ON a.tercero_id = t.id
                 WHERE a.comprobante_id = :cid ORDER BY a.id ASC"
            );
            $stmtL->execute([':cid' => $id]);
            $comp['lineas'] = $stmtL->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($comp);
        } else {
            // Listado de comprobantes
            $desde  = (!empty($_GET['desde'])) ? $_GET['desde'] : date('Y-m-01');
            $hasta  = (!empty($_GET['hasta'])) ? $_GET['hasta'] : date('Y-m-d');
            $estado = $_GET['estado'] ?? 'registrado';
            $page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $offset = ($page - 1) * $limit;
            $filtroAmpliado = false;
            
            $whereEstado = ($estado === 'todos') ? "1=1" : "c.estado = :e";
            $whereBase = "WHERE c.empresa_id = :eid
                          AND $whereEstado";
            $whereFecha = $asientosTieneFecha
                ? "AND (
                         c.fecha BETWEEN :d AND :h
                         OR EXISTS (
                             SELECT 1
                             FROM asientos a
                             WHERE a.comprobante_id = c.id
                               AND a.fecha BETWEEN :d2 AND :h2
                         )
                     )"
                : "AND c.fecha BETWEEN :d AND :h";
            $where = "$whereBase
                      $whereFecha";

            $stmtCount = $db->prepare("SELECT COUNT(*) FROM comprobantes c $where");
            $stmtCount->bindValue(':eid', $eid, PDO::PARAM_INT);
            if ($estado !== 'todos') $stmtCount->bindValue(':e', $estado);
            $stmtCount->bindValue(':d', $desde);
            $stmtCount->bindValue(':h', $hasta);
            if ($asientosTieneFecha) {
                $stmtCount->bindValue(':d2', $desde);
                $stmtCount->bindValue(':h2', $hasta);
            }
            $stmtCount->execute();
            $totalRecords = (int)$stmtCount->fetchColumn();

            if ($totalRecords === 0) {
                $stmtCountAll = $db->prepare("SELECT COUNT(*) FROM comprobantes c $whereBase");
                $stmtCountAll->bindValue(':eid', $eid, PDO::PARAM_INT);
                if ($estado !== 'todos') $stmtCountAll->bindValue(':e', $estado);
                $stmtCountAll->execute();
                $totalAllRecords = (int)$stmtCountAll->fetchColumn();

                if ($totalAllRecords > 0) {
                    $where = $whereBase;
                    $totalRecords = $totalAllRecords;
                    $filtroAmpliado = true;
                }
            }

            $fechaAsientoSql = $asientosTieneFecha
                ? "COALESCE((SELECT MAX(a.fecha) FROM asientos a WHERE a.comprobante_id = c.id AND a.fecha IS NOT NULL), c.fecha) AS fecha_asiento,"
                : "c.fecha AS fecha_asiento,";
            $sql = "SELECT c.*, tc.codigo as tipo_comp, tc.nombre as tipo_nombre, t.razon_social as tercero,
                            {$fechaAsientoSql}
                            u.username as usuario_modifico
                     FROM comprobantes c
                     JOIN tipos_comprobante tc ON c.tipo_comp_id = tc.id
                     LEFT JOIN terceros t ON c.tercero_id = t.id
                     LEFT JOIN usuarios u ON c.usuario_id = u.id
                     $where
                     ORDER BY fecha_asiento DESC, c.id DESC
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':eid', $eid, PDO::PARAM_INT);
            if ($estado !== 'todos') $stmt->bindValue(':e', $estado);
            if (!$filtroAmpliado) {
                $stmt->bindValue(':d', $desde);
                $stmt->bindValue(':h', $hasta);
                if ($asientosTieneFecha) {
                    $stmt->bindValue(':d2', $desde);
                    $stmt->bindValue(':h2', $hasta);
                }
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode([
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'filters_relaxed' => $filtroAmpliado,
                'pagination' => [
                    'total' => $totalRecords,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($totalRecords / $limit)
                ]
            ]);
        }
    }
    elseif ($method === 'DELETE') {
        Auth::requirePermiso('comprobantes', 'd');
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if (!$id) throw new Exception("ID de comprobante no proporcionado.");

        $db->beginTransaction();

        $stmtLines = $db->prepare("SELECT cuenta_id, debito, credito, periodo_id FROM asientos a JOIN comprobantes c ON a.comprobante_id = c.id WHERE c.id = :id AND c.empresa_id = :eid");
        $stmtLines->execute([':id' => $id, ':eid' => $eid]);
        $lineas = $stmtLines->fetchAll(PDO::FETCH_ASSOC);

        foreach ($lineas as $l) {
            actualizarSaldoCuenta($db, $eid, (int)$l['periodo_id'], (int)$l['cuenta_id'], -(float)$l['debito'], -(float)$l['credito']);
        }

        $stmtDel = $db->prepare("DELETE FROM comprobantes WHERE id = :id AND empresa_id = :eid");
        $stmtDel->execute([':id' => $id, ':eid' => $eid]);

        if ($stmtDel->rowCount() === 0) {
            throw new Exception("El comprobante no existía o no pertenece a esta empresa.");
        }

        $db->commit();
        echo json_encode(['success' => true]);
    }
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Actualiza la tabla de saldos_periodo para reportes veloces (Balance/Estado Resultados)
 */
function actualizarSaldoCuenta($db, int $empresaId, int $periodoId, int $cuentaId, float $debito, float $credito) {
    // Upsert saldo del periodo
    $stmt = $db->prepare(
        "INSERT INTO saldos_periodo (empresa_id, periodo_id, cuenta_id, total_debito, total_credito)
         VALUES (:eid, :pid, :cta, :deb, :cre)
         ON DUPLICATE KEY UPDATE total_debito = total_debito + VALUES(total_debito), total_credito = total_credito + VALUES(total_credito)"
    );
    $stmt->execute([':eid' => $empresaId, ':pid' => $periodoId, ':cta' => $cuentaId, ':deb' => $debito, ':cre' => $credito]);
}

function tableHasColumn(PDO $db, string $table, string $column): bool {
    static $cache = [];

    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $stmt = $db->prepare(
        "SELECT 1
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table
           AND COLUMN_NAME = :column
         LIMIT 1"
    );
    $stmt->execute([
        ':table' => $table,
        ':column' => $column,
    ]);

    $cache[$key] = (bool)$stmt->fetchColumn();
    return $cache[$key];
}
