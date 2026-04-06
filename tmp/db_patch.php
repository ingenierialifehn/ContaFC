<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();

try {
    $sql = "CREATE TABLE IF NOT EXISTS com_contratos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        cliente_id INT NOT NULL,
        producto_id INT NOT NULL,
        monto DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        dia_facturacion INT NOT NULL DEFAULT 1,
        fecha_inicio DATE NOT NULL,
        ultima_factura DATE DEFAULT NULL,
        activa TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "Tabla com_contratos creada correctamente.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
