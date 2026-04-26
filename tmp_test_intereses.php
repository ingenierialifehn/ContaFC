<?php
require __DIR__.'/bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();

$sql = "SELECT p.codigo, p.nombre, p.id
        FROM puc_cuentas p
        WHERE p.nombre LIKE '%clientes%' OR p.nombre LIKE '%cobrar%'";
$stmt = $db->query($sql);
$cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($cuentas);

$sql = "SELECT SUM(a.debito) as deb, SUM(a.credito) as cred 
        FROM asientos a 
        JOIN puc_cuentas p ON a.cuenta_id = p.id 
        JOIN comprobantes c ON a.comprobante_id = c.id
        WHERE p.nombre LIKE '%nacionales%' AND p.nombre LIKE '%clientes%' AND c.estado = 'registrado'";
$stmt2 = $db->query($sql);
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));

$sql = "SELECT p.nombre, a.debito, a.credito, a.descripcion, c.fecha
        FROM asientos a 
        JOIN puc_cuentas p ON a.cuenta_id = p.id 
        JOIN comprobantes c ON a.comprobante_id = c.id
        WHERE p.nombre LIKE '%clientes%' AND a.descripcion LIKE '%Intereses Financia%' AND c.estado = 'registrado'";
$stmt3 = $db->query($sql);
print_r($stmt3->fetchAll(PDO::FETCH_ASSOC));

