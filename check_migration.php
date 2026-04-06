<?php
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;

try {
    $db = Database::getInstance()->getPdo();
    $stmt = $db->query("SELECT * FROM comprobantes WHERE id = 9999");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo "✅ Comprobante 9999 EXISTE.\n";
        print_r($row);
        
        $countStmt = $db->query("SELECT COUNT(*) FROM asientos WHERE comprobante_id = 9999");
        echo "📊 Total de asientos: " . $countStmt->fetchColumn() . "\n";
    } else {
        echo "❌ Comprobante 9999 NO EXISTE en la base de datos.\n";
    }
    
    $empStmt = $db->query("SELECT id, nombre FROM empresas");
    echo "\n🏢 Empresas encontradas:\n";
    while ($emp = $empStmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$emp['id']} - Nombre: {$emp['nombre']}\n";
    }

} catch (Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
