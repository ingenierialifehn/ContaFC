<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();

echo "Verificando creditos negativos con 'intereses financiacion'...\n";

// Búsqueda case-insensitive, ignorando acentos puede ser complicado en SQL sin collation adecuada, 
// así que usamos LIKE y algunas variantes.
$sql = "SELECT id, conteo, fecha, debito, credito, descripcion FROM asientos 
        WHERE credito < 0 AND (LOWER(descripcion) LIKE '%intereses%financiacion%' 
        OR LOWER(descripcion) LIKE '%intereses%financiaci_n%')";

$stmt = $db->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Encontrados: " . count($rows) . " registros con creditos negativos para intereses financiacion.\n";

$updateStmt = $db->prepare("UPDATE asientos SET credito = ABS(credito) WHERE id = ?");

$affected = 0;
foreach ($rows as $row) {
    $updateStmt->execute([$row['id']]);
    $affected++;
}

echo "Se actualizaron $affected registros cambiando el credito negativo a positivo.\n";

// ¿Qué pasa con los débitos negativos?
$sqlDeb = "SELECT id, conteo, fecha, debito, credito, descripcion FROM asientos 
           WHERE debito < 0 AND (LOWER(descripcion) LIKE '%intereses%financiacion%' 
           OR LOWER(descripcion) LIKE '%intereses%financiaci_n%')";
$stmtDeb = $db->query($sqlDeb);
$rowsDeb = $stmtDeb->fetchAll(PDO::FETCH_ASSOC);

echo "Encontrados: " . count($rowsDeb) . " registros con debitos negativos para intereses financiacion.\n";
if (count($rowsDeb) > 0) {
    $updateDebStmt = $db->prepare("UPDATE asientos SET debito = ABS(debito) WHERE id = ?");
    $affectedDeb = 0;
    foreach ($rowsDeb as $row) {
        $updateDebStmt->execute([$row['id']]);
        $affectedDeb++;
    }
    echo "Se actualizaron $affectedDeb registros cambiando el debito negativo a positivo.\n";
}

echo "Verificando el impacto en el balance general...\n";
$stmtCheck = $db->query("SELECT SUM(debito - credito) as saldo_neto FROM asientos a 
                         JOIN comprobantes c ON a.comprobante_id = c.id 
                         WHERE a.cuenta_id = (SELECT id FROM puc_cuentas WHERE codigo = '11050101' LIMIT 1) 
                         AND YEAR(c.fecha) <= 2023");
$saldo2023 = $stmtCheck->fetchColumn();
echo "Saldo neto 11050101 para 2023: $saldo2023\n";

echo "¡Listo!\n";
