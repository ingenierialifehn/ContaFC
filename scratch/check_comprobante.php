<?php
require __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();
$res = $db->query("SELECT * FROM comprobantes WHERE id = 9999")->fetch(PDO::FETCH_ASSOC);
if (!$res) {
    echo "Comprobante 9999 NO existe. Creándolo...\n";
    $db->exec("INSERT INTO comprobantes (id, nombre, codigo, empresa_id) VALUES (9999, 'RESTAURACION HISTORICA', 'REST', 1)");
    echo "Comprobante 9999 creado.\n";
} else {
    echo "Comprobante 9999 ya existe:\n";
    print_r($res);
}
