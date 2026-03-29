<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
header('Content-Type: application/json');

$db  = Database::getInstance()->getPdo();
$eid = Auth::empresaId();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $stmt = $db->prepare("SELECT * FROM com_productos WHERE empresa_id = :eid AND activo = 1 ORDER BY nombre ASC");
        $stmt->execute([':eid' => $eid]);
        echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } 
    elseif ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare(
            "INSERT INTO com_productos (empresa_id, codigo, nombre, tipo, precio_venta, tasa_isv, maneja_inventario, maneja_lotes) 
             VALUES (:eid, :cod, :nom, :tip, :pv, :tiv, :mi, :ml)"
        );
        $stmt->execute([
            ':eid' => $eid,
            ':cod' => trim($body['codigo']),
            ':nom' => trim($body['nombre']),
            ':tip' => $body['tipo'],
            ':pv'  => (float)$body['precio_venta'],
            ':tiv' => (float)$body['tasa_isv'],
            ':mi'  => (int)$body['maneja_inventario'],
            ':ml'  => (int)$body['maneja_lotes']
        ]);
        echo json_encode(['success' => true]);
    }
    elseif ($method === 'PUT') {
        $body = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare(
            "UPDATE com_productos SET codigoTarget = :cod, nombre = :nom, tipo = :tip, precio_venta = :pv, 
             tasa_isv = :tiv, maneja_inventario = :mi, maneja_lotes = :ml 
             WHERE id = :id AND empresa_id = :eid"
        );
        // Nota: codigoTarget -> codigo en la tabla Real. Corregiremos el typo si lo hubiera.
        $stmt = $db->prepare(
            "UPDATE com_productos SET codigo = :cod, nombre = :nom, tipo = :tip, precio_venta = :pv, 
             tasa_isv = :tiv, maneja_inventario = :mi, maneja_lotes = :ml 
             WHERE id = :id AND empresa_id = :eid"
        );
        $stmt->execute([
            ':cod' => trim($body['codigo']),
            ':nom' => trim($body['nombre']),
            ':tip' => $body['tipo'],
            ':pv'  => (float)$body['precio_venta'],
            ':tiv' => (float)$body['tasa_isv'],
            ':mi'  => (int)$body['maneja_inventario'],
            ':ml'  => (int)$body['maneja_lotes'],
            ':id'  => (int)$body['id'],
            ':eid' => $eid
        ]);
        echo json_encode(['success' => true]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
