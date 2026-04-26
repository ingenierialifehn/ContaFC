<?php
require 'bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$sql = "SELECT a.conteo, a.id, c.fecha, a.descripcion, a.debito, a.credito 
        FROM asientos a 
        JOIN comprobantes c ON a.comprobante_id = c.id 
        JOIN puc_cuentas p ON a.cuenta_id = p.id 
        WHERE p.nombre LIKE '%clientes%' 
          AND a.descripcion LIKE '%Intereses%Financia%' 
          AND a.credito < 0 
        LIMIT 5";
$stmt = $db->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($results, JSON_PRETTY_PRINT);
