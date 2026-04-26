<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
$cols = $db->query("DESCRIBE comprobantes")->fetchAll(PDO::FETCH_COLUMN);
echo implode(", ", $cols) . "\n";
