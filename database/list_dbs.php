<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;charset=utf8mb4", 'root', 'root');
print_r($db->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN));
