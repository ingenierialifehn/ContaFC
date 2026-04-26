<?php
try {
    $db = new PDO("mysql:host=172.18.0.3;dbname=contafc", "root", "root");
    $stmt = $db->query("SELECT * FROM saldos_periodo WHERE cuenta_id BETWEEN 1448 AND 1477");
    while($r = $stmt->fetch(PDO::FETCH_ASSOC)) print_r($r);
} catch (Exception $e) {
    echo $e->getMessage();
}
