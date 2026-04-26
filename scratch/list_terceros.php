<?php
try {
    $db = new PDO("mysql:host=172.18.0.3;dbname=contafc", "root", "root");
    $stmt = $db->query("SELECT nit_cc, razon_social FROM terceros LIMIT 50");
    while($r = $stmt->fetch(PDO::FETCH_ASSOC)) echo $r['nit_cc'] . ' | ' . $r['razon_social'] . "\n";
} catch (Exception $e) {
    echo $e->getMessage();
}
