<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');
$db->exec("DELETE FROM comprobantes WHERE observaciones = 'ASIENTO DE APERTURA PATRIMONIO (AJUSTE MIGRACION)'");
echo "Ajuste artificial eliminado para restaurar la realidad del sistema viejo.\n";
