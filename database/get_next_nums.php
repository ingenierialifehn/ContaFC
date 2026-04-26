<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');

// Obtenemos el último número por (empresa, tipo)
$nums = $db->query("SELECT empresa_id, tipo_comp_id, MAX(numero) as max_num 
                   FROM comprobantes 
                   GROUP BY empresa_id, tipo_comp_id")->fetchAll(PDO::FETCH_ASSOC);

$nextNum = [];
foreach ($nums as $n) {
    $nextNum[$n['empresa_id']][$n['tipo_comp_id']] = $n['max_num'] + 1;
}

file_put_contents(__DIR__ . '/next_nums.json', json_encode($nextNum));
echo "Next numbers saved.\n";
