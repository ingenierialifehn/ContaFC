<?php
try {
    $db = new PDO("mysql:host=172.18.0.3;dbname=contafc", "root", "root");
    $ids = [1461, 1462, 1463, 1464, 1465, 1466, 1467, 1468, 1469, 1470, 1471, 1472, 1473, 1474, 1475, 1476, 1477];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("SELECT a.*, p.codigo FROM asientos a JOIN puc_cuentas p ON a.cuenta_id = p.id WHERE a.cuenta_id IN ($placeholders) LIMIT 20");
    $stmt->execute($ids);
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($res, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo $e->getMessage();
}
