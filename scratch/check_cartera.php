<?php
try {
    $db = new PDO("mysql:host=172.18.0.3;dbname=contafc", "root", "root");
    $stmt = $db->query("SELECT * FROM cartera_creditos LIMIT 20");
    while($r = $stmt->fetch(PDO::FETCH_ASSOC)) print_r($r);
} catch (Exception $e) {
    echo $e->getMessage();
}
