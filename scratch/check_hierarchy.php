<?php
try {
    $db = new PDO("mysql:host=172.18.0.3;dbname=contafc", "root", "root");
    $stmt = $db->query("SELECT p.codigo, p.nombre, p.codigo_padre, parent.nombre as parent_name 
                        FROM puc_cuentas p 
                        LEFT JOIN puc_cuentas parent ON p.codigo_padre = parent.codigo AND p.empresa_id = parent.empresa_id
                        WHERE p.codigo IN ('1002664322', '1021192323', '1037969539', '1050034305')");
    while($r = $stmt->fetch(PDO::FETCH_ASSOC)) print_r($r);
} catch (Exception $e) {
    echo $e->getMessage();
}
