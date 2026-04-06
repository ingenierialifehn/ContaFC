<?php
require 'bootstrap.php';
use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();

ob_start();
echo "--- LISTA DE EMPRESAS --- \n";
$stmt = $db->query("SELECT id, nombre FROM empresas");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- CONTEO DE PERIODOS POR EMPRESA --- \n";
$stmt = $db->query("SELECT empresa_id, anio, COUNT(*) as m_count FROM periodos GROUP BY empresa_id, anio ORDER BY empresa_id, anio");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- CONTEO DE COMPROBANTES POR EMPRESA --- \n";
$stmt = $db->query("SELECT empresa_id, YEAR(fecha) as anio, COUNT(*) as c_count FROM comprobantes GROUP BY empresa_id, anio ORDER BY empresa_id, anio");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$out = ob_get_clean();
file_put_contents('/var/www/html/contafc/summary_status.txt', $out);
