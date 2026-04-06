<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;

header('Content-Type: application/json; charset=utf-8');

try {
    $db = Database::getInstance()->getPdo();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $results = [];

    // 1. Asegurar Tabla 'usuarios'
    $stmt = $db->query("DESCRIBE usuarios");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('permisos', $cols)) {
        $ver = $db->query("SELECT VERSION()")->fetchColumn();
        $type = (version_compare($ver, '5.7.8', '>=')) ? 'JSON' : 'LONGTEXT';
        $db->exec("ALTER TABLE usuarios ADD COLUMN permisos $type DEFAULT NULL");
        $results[] = "Agregada columna 'permisos' ($type).";
    }

    if (!in_array('empresa_id', $cols)) {
        $emp_id = $db->query("SELECT id FROM empresas LIMIT 1")->fetchColumn();
        if (!$emp_id) throw new Exception("Se requiere al menos una empresa en la tabla 'empresas'.");
        $db->exec("ALTER TABLE usuarios ADD COLUMN empresa_id SMALLINT UNSIGNED NOT NULL DEFAULT $emp_id AFTER id");
        $results[] = "Agregada columna 'empresa_id'.";
    }

    // 2. Asegurar Tabla 'usuarios_empresas' (Relación N a N)
    $db->exec("CREATE TABLE IF NOT EXISTS `usuarios_empresas` (
        `usuario_id` INT UNSIGNED NOT NULL,
        `empresa_id` SMALLINT UNSIGNED NOT NULL,
        PRIMARY KEY (`usuario_id`, `empresa_id`),
        CONSTRAINT `fk_ue_usuario_fix` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_ue_empresa_fix` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Verificar si se acaba de crear (o si está vacía) y poblarla con el primer usuario
    $countUE = $db->query("SELECT COUNT(*) FROM usuarios_empresas")->fetchColumn();
    if ($countUE == 0) {
        $user_id = $db->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn();
        $emp_id  = $db->query("SELECT id FROM empresas LIMIT 1")->fetchColumn();
        if ($user_id && $emp_id) {
            $db->exec("INSERT INTO usuarios_empresas (usuario_id, empresa_id) VALUES ($user_id, $emp_id)");
            $results[] = "Poblando tabla cruzada con el primer usuario/empresa.";
        }
    }

    echo json_encode([
        'success' => true,
        'message' => empty($results) ? 'La estructura ya es correcta.' : 'Reparación de esquema completada.',
        'details' => $results
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
