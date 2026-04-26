<?php
require_once __DIR__ . '/../bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();

$year = 2023;

// 1. Total balance
$sql = "SELECT SUM(debito) as total_debito, SUM(credito) as total_credito FROM asientos WHERE YEAR(fecha) = :y";
$stmt = $db->prepare($sql);
$stmt->execute(['y' => $year]);
$balance = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Entries without CONTEO
$sql2 = "SELECT COUNT(*) as count FROM asientos WHERE YEAR(fecha) = :y AND (conteo IS NULL OR conteo = '')";
$stmt2 = $db->prepare($sql2);
$stmt2->execute(['y' => $year]);
$noConteo = $stmt2->fetch(PDO::FETCH_ASSOC);

// 3. Check for unbalanced comprobantes
$sql3 = "
    SELECT comprobante_id, SUM(debito) as d, SUM(credito) as c, (SUM(debito) - SUM(credito)) as diff
    FROM asientos 
    WHERE YEAR(fecha) = :y
    GROUP BY comprobante_id
    HAVING ABS(diff) > 0.001
    LIMIT 20
";
$stmt3 = $db->prepare($sql3);
$stmt3->execute(['y' => $year]);
$unbalanced = $stmt3->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'balance' => $balance,
    'no_conteo' => $noConteo['count'],
    'unbalanced_vouchers' => $unbalanced
], JSON_PRETTY_PRINT);
