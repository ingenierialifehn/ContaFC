<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

echo "BUSCANDO IDs DE CUENTAS PARA EMPRESA 1...\n";
$accounts = ['11020104', '12010106', '36100101'];
foreach ($accounts as $code) {
    $stmt = $db->prepare("SELECT id, nombre FROM puc_cuentas WHERE codigo = :code AND empresa_id = 1");
    $stmt->execute([':code' => $code]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($res) {
        echo "Cuenta $code: ID = {$res['id']} ({$res['nombre']})\n";
    } else {
        echo "Cuenta $code: NO ENCONTRADA\n";
    }
}
?>
