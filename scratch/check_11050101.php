<?php
try {
    $db = new PDO("mysql:host=172.18.0.3;dbname=contafc", "root", "root");
    $stmt = $db->query("SELECT * FROM puc_cuentas WHERE codigo = '11050101'");
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo $e->getMessage();
}
