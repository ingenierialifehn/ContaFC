<?php
try {
    $db = new PDO("mysql:host=172.18.0.3;dbname=contafc", "root", "root");
    $stmt = $db->query("SELECT id, codigo, nombre FROM puc_cuentas WHERE id BETWEEN 1440 AND 1500 ORDER BY id");
    while($r = $stmt->fetch(PDO::FETCH_ASSOC)) echo $r['id'] . ' | ' . $r['codigo'] . ' | ' . $r['nombre'] . "\n";
} catch (Exception $e) {
    echo $e->getMessage();
}
