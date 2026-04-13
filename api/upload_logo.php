<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Auth;

header('Content-Type: application/json; charset=utf-8');
Auth::requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

if (!isset($_FILES['logo'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No se recibió ningún archivo.']);
    exit;
}

$file = $_FILES['logo'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 2 * 1024 * 1024; // 2MB

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Error al subir el archivo.']);
    exit;
}

if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['error' => 'Tipo de archivo no permitido. Solo se aceptan JPG, PNG, GIF y WEBP.']);
    exit;
}

if ($file['size'] > $maxSize) {
    echo json_encode(['error' => 'El archivo es demasiado grande. Máximo 2MB.']);
    exit;
}

$uploadDir = __DIR__ . '/../assets/logos/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generar un nombre único para el archivo
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('logo_', true) . '.' . $extension;
$destination = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $destination)) {
    echo json_encode([
        'success' => true,
        'path' => 'assets/logos/' . $filename
    ]);
} else {
    echo json_encode(['error' => 'No se pudo guardar el archivo en el servidor.']);
}
