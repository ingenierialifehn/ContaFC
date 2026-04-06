<?php
require 'bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

ob_start();
echo "--- COMPROBANTES 2023 --- \n";
$stmt = $db->query("SELECT id, fecha, periodo_id FROM comprobantes WHERE YEAR(fecha) = 2023 AND empresa_id = 1");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- PERIODOS --- \n";
$stmt = $db->query("SELECT * FROM periodos WHERE empresa_id = 1");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$out = ob_get_clean();
file_put_contents('/var/www/html/contafc/check_2023_mapping.txt', $out);
