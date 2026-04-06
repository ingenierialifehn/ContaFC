<?php
require 'bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();

ob_start();
echo "--- PERIODOS PARA EMPRESA 1 ---\n";
$stmt = $db->query("SELECT * FROM periodos WHERE empresa_id = 1 ORDER BY anio, mes");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- MIN YEAR EN COMPROBANTES ---\n";
$stmt = $db->query("SELECT MIN(YEAR(fecha)) as min_year FROM comprobantes WHERE empresa_id = 1");
print_r($stmt->fetch(PDO::FETCH_ASSOC));

echo "\n--- COMPROBANTES POR AÑO/MES ---\n";
$stmt = $db->query("SELECT YEAR(fecha) as anio, MONTH(fecha) as mes, COUNT(*) as cant FROM comprobantes WHERE empresa_id = 1 GROUP BY anio, mes ORDER BY anio, mes");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$out = ob_get_clean();
file_put_contents('/var/www/html/contafc/check_periods_v2_results.txt', $out);
