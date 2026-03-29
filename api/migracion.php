<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;
use ContaFC\Core\Database;

Auth::requireAuth();
Auth::requireRol('admin');

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$uploadDir = __DIR__ . '/../tmp';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

try {
    if ($method !== 'POST') throw new Exception("Método no permitido");

    if (!isset($_FILES['file'])) throw new Exception("No se recibió el archivo GDB");

    $file = $_FILES['file'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (strtolower($ext) !== 'gdb') throw new Exception("Formato inválido. Debe ser .gdb");

    $tempPath = $uploadDir . '/' . uniqid('migr_') . '.gdb';
    if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
        throw new Exception("Error al mover el archivo al servidor.");
    }

    // ─── Conexión a Firebird ──────────────────────────────────────────────────
    // IMPORTANTE: El driver pdo_firebird debe estar activo.
    // La cadena de conexión en Linux/Docker para archivos locales:
    $dsnF = "firebird:dbname=" . $tempPath . ";charset=UTF8";
    
    try {
        $fb = new PDO($dsnF, "SYSDBA", "masterkey");
        $fb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        throw new Exception("Error de conexión a Firebird: " . $e->getMessage());
    }

    $db = Database::getInstance()->getPdo();
    $eid = Auth::empresaId();

    $db->beginTransaction();

    // 1. Mapear Terceros (WXManager: suele llamarse CLIENTES o TERCEROS)
    // Intentaremos detectar la tabla.
    $tercerosTable = 'TERCEROS';
    $stmtT = $fb->query("SELECT * FROM $tercerosTable");
    $countT = 0;
    while ($r = $stmtT->fetch(PDO::FETCH_ASSOC)) {
        $stmtIns = $db->prepare(
            "INSERT IGNORE INTO terceros (empresa_id, codigo, razon_social, nit_cc, tipo_documento, tipo_tercero, activo)
             VALUES (:eid, :cod, :nom, :nit, 'RTN', 'cliente', 1)"
        );
        $stmtIns->execute([
            ':eid' => $eid,
            ':cod' => $r['CODIGO'] ?? $r['ID'] ?? uniqid(),
            ':nom' => $r['NOMBRE'] ?? $r['RAZON_SOCIAL'] ?? 'Sin nombre',
            ':nit' => $r['RTN']    ?? $r['NIT']    ?? $r['CEDULA'] ?? ''
        ]);
        $countT++;
    }

    // 2. Mapear PUC (WXManager: suele ser CUENTAS)
    $stmtC = $fb->query("SELECT * FROM CUENTAS"); // Ajustar según estructura real detectada
    $countC = 0;
    while ($r = $stmtC->fetch(PDO::FETCH_ASSOC)) {
        $stmtIns = $db->prepare(
            "INSERT IGNORE INTO puc_cuentas (empresa_id, codigo, nombre, nivel, naturaleza, tipo_cuenta, acepta_movimiento)
             VALUES (:eid, :cod, :nom, :niv, :nat, :tipo, :acc)"
        );
        $stmtIns->execute([
            ':eid' => $eid,
            ':cod' => $r['CODIGO'],
            ':nom' => $r['NOMBRE'],
            ':niv' => strlen($r['CODIGO']) <= 1 ? 1 : (strlen($r['CODIGO']) <= 2 ? 2 : (strlen($r['CODIGO']) <= 4 ? 3 : 4)),
            ':nat' => ($r['NATURALEZA'] ?? 'D') == 'D' ? 'D' : 'C',
            ':tipo'=> 'A', 
            ':acc' => (strlen($r['CODIGO']) > 4 ? 1 : 0)
        ]);
        $countC++;
    }

    $db->commit();
    
    // Limpieza
    unlink($tempPath);

    echo json_encode([
        'success' => true,
        'message' => "Migración exitosa: $countT terceros y $countC cuentas importadas.",
        'details' => ['terceros' => $countT, 'cuentas' => $countC]
    ]);

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    if (isset($tempPath) && file_exists($tempPath)) unlink($tempPath);
    
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
