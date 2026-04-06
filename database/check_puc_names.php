<?php
require_once __DIR__ . '/../bootstrap.php';
$stmt = ContaFC\Core\Database::getInstance()->getPdo()->query("SELECT codigo, nombre FROM puc_cuentas LIMIT 20");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['codigo'] . ' | ' . $row['nombre'] . PHP_EOL;
}
