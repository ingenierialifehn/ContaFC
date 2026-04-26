<?php
require 'bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$desde = '2023-01-01';
$hasta = '2023-12-31';
$eid = 1;
$estado = 'registrado';

$whereEstado = "c.estado = :e";
$whereBase = "WHERE c.empresa_id = :eid AND $whereEstado";
$whereFecha = "AND (c.fecha BETWEEN :d AND :h OR EXISTS (SELECT 1 FROM asientos a WHERE a.comprobante_id = c.id AND a.fecha BETWEEN :d2 AND :h2))";
$where = "$whereBase $whereFecha";

$sql = "SELECT COUNT(*) as total_comp, COALESCE(SUM((SELECT COUNT(*) FROM asientos a WHERE a.comprobante_id = c.id)), 0) as total_asientos FROM comprobantes c $where";
$stmtCount = $db->prepare($sql);
$stmtCount->bindValue(':eid', $eid);
$stmtCount->bindValue(':e', $estado);
$stmtCount->bindValue(':d', $desde);
$stmtCount->bindValue(':h', $hasta);
$stmtCount->bindValue(':d2', $desde);
$stmtCount->bindValue(':h2', $hasta);
$stmtCount->execute();
print_r($stmtCount->fetch(PDO::FETCH_ASSOC));
