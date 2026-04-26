<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
print_r($db->query("SELECT id, ano, mes, empresa_id FROM periodos")->fetchAll(PDO::FETCH_ASSOC));
