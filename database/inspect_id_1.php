<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
$stmt = $db->query("SELECT * FROM asientos WHERE id = 1");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
