<?php
$ip = '172.18.0.3';
$db = new PDO("mysql:host=$ip;port=3306;dbname=contafc;charset=utf8mb4", 'root', 'root');

// Calculamos el balance asumiendo que los créditos negativos SUMAN (comportamiento original/erróneo del sistema viejo)
$sql = "SELECT SUM(
            CASE 
                WHEN (descripcion LIKE '%Intereses%' AND descripcion LIKE '%Financiaci%') AND credito > 0 THEN (debito - credito)
                WHEN (descripcion LIKE '%Intereses%' AND descripcion LIKE '%Financiaci%') AND credito < 0 THEN (debito + ABS(credito)) 
                ELSE (debito - credito)
            END
        ) as saldo_viejo
        FROM asientos 
        WHERE cuenta_id = (SELECT id FROM puc_cuentas WHERE codigo = '11050101') 
        AND YEAR(fecha) <= 2023";

echo "Balance simulado 'tipo sistema viejo' para 11050101: " . $db->query($sql)->fetchColumn() . "\n";
echo "Balance actual en MySQL: " . $db->query("SELECT SUM(debito - credito) FROM asientos WHERE cuenta_id = (SELECT id FROM puc_cuentas WHERE codigo = '11050101') AND YEAR(fecha) <= 2023")->fetchColumn() . "\n";
