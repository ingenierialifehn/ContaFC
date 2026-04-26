<?php
try {
    $db = new PDO("mysql:host=172.18.0.3;dbname=contafc", "root", "root");
    $stmt = $db->query("SELECT nit_cc, razon_social FROM terceros WHERE nit_cc IN ('1002664322', '1021192323', '1037969539', '1050034305')");
    while($r = $stmt->fetch(PDO::FETCH_ASSOC)) print_r($r);
} catch (Exception $e) {
    echo $e->getMessage();
}
