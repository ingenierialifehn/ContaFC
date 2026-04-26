<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
$periods = $db->query("SELECT id, anio, mes, empresa_id FROM periodos")->fetchAll(PDO::FETCH_ASSOC);

$map = [];
foreach ($periods as $p) {
    $map[$p['empresa_id']][$p['anio']][$p['mes']] = $p['id'];
}

file_put_contents(__DIR__ . '/period_map.json', json_encode($map));
echo "Period map saved.\n";
