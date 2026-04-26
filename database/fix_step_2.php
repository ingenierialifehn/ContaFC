<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();

echo "1. Corrigiendo tipos de cuentas en puc_cuentas...\n";
$queries = [
    "UPDATE puc_cuentas SET naturaleza = 'D', tipo_cuenta = 'A' WHERE codigo LIKE '1%' AND nombre NOT LIKE '%Deprec%' AND nombre NOT LIKE '%Reserva%'",
    "UPDATE puc_cuentas SET naturaleza = 'C', tipo_cuenta = 'A' WHERE codigo LIKE '1%' AND (nombre LIKE '%Deprec%' OR nombre LIKE '%Reserva%')",
    "UPDATE puc_cuentas SET naturaleza = 'C', tipo_cuenta = 'P' WHERE codigo LIKE '2%'",
    "UPDATE puc_cuentas SET naturaleza = 'C', tipo_cuenta = 'R' WHERE codigo LIKE '3%'",
    "UPDATE puc_cuentas SET naturaleza = 'C', tipo_cuenta = 'R' WHERE codigo LIKE '4%'",
    "UPDATE puc_cuentas SET naturaleza = 'D', tipo_cuenta = 'G' WHERE codigo LIKE '5%'",
    "UPDATE puc_cuentas SET naturaleza = 'D', tipo_cuenta = 'G' WHERE codigo LIKE '6%'",
    "UPDATE puc_cuentas SET naturaleza = 'D', tipo_cuenta = 'G' WHERE codigo LIKE '7%'"
];

foreach ($queries as $q) {
    try {
        $stmt = $db->query($q);
        echo "Filas afectadas: " . $stmt->rowCount() . " | Query: " . substr($q, 0, 50) . "...\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

echo "\n2. Leyendo dbf_data.json...\n";
$jsonStr = file_get_contents(__DIR__ . '/dbf_data.json');
$dbfRows = json_decode($jsonStr, true);

$dbfByConteo = [];
$dbfByProps = [];

$stmt = $db->query("SELECT id, codigo, nombre FROM puc_cuentas");
$cuentaIdToCode = [];
$cuentaCodeToName = [];
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $cuentaIdToCode[$r['id']] = $r['codigo'];
    $cuentaCodeToName[$r['codigo']] = $r['nombre'];
}

foreach ($dbfRows as $row) {
    $dbfByConteo[$row['conteo']] = $row;
    
    $deb = number_format((float)$row['debito'], 4, '.', '');
    $cre = number_format((float)$row['credito'], 4, '.', '');
    $acct = $row['acct'];
    
    $key = $acct . '|' . $deb . '|' . $cre;
    if (!isset($dbfByProps[$key])) {
        $dbfByProps[$key] = [];
    }
    $dbfByProps[$key][] = $row;
}

echo "Buscando asientos con conteo IS NULL o fecha no coincidentes...\n";
$stmt = $db->query("SELECT id, cuenta_id, debito, credito, descripcion, fecha FROM asientos WHERE conteo IS NULL");
$asientosNull = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total asientos con conteo NULL: " . count($asientosNull) . "\n";

$matched = 0;
$updatedDates = 0;
$unmatched = 0;

$updateStmt = $db->prepare("UPDATE asientos SET conteo = ?, fecha = ? WHERE id = ?");

foreach ($asientosNull as $a) {
    $cId = $a['cuenta_id'];
    $codigo = $cuentaIdToCode[$cId] ?? '';
    
    $deb = number_format((float)$a['debito'], 4, '.', '');
    $cre = number_format((float)$a['credito'], 4, '.', '');
    
    $key = $codigo . '|' . $deb . '|' . $cre;
    
    if (isset($dbfByProps[$key]) && count($dbfByProps[$key]) > 0) {
        $match = array_shift($dbfByProps[$key]);
        $matched++;
        
        $updateStmt->execute([$match['conteo'], $match['fecha'], $a['id']]);
        if ($a['fecha'] !== $match['fecha']) {
            $updatedDates++;
        }
    } else {
        $unmatched++;
    }
}

echo "Asientos con conteo NULL matcheados y actualizados: $matched\n";
echo "De los cuales, se actualizó una fecha distinta en: $updatedDates\n";
echo "No se pudieron matchear: $unmatched\n";

echo "\n3. Revisando nombres de 'Cuenta Migrada' en puc_cuentas...\n";
$stmt = $db->query("SELECT id, codigo, nombre FROM puc_cuentas WHERE nombre LIKE 'Cuenta Migrada%'");
$migradas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($migradas) > 0) {
    echo "Hay " . count($migradas) . " cuentas migradas sin nombre real.\n";
    $updNombre = $db->prepare("UPDATE puc_cuentas SET nombre = ? WHERE id = ?");
    $fixedNames = 0;
    foreach ($migradas as $m) {
        $posibleNombre = null;
        foreach ($dbfByConteo as $d) {
            if ($d['acct'] === $m['codigo']) {
                $posibleNombre = trim($d['desc'] ?? '');
                if (empty($posibleNombre)) $posibleNombre = trim($d['detalle'] ?? '');
                if (!empty($posibleNombre)) break;
            }
        }
        
        if ($posibleNombre && stripos($posibleNombre, 'CONTRATO') === false) {
            $posibleNombre = strtoupper(substr($posibleNombre, 0, 80));
            $updNombre->execute([$posibleNombre, $m['id']]);
            $fixedNames++;
        }
    }
    echo "Se intentó corregir el nombre a $fixedNames cuentas basadas en la descripción del DBF.\n";
} else {
    echo "No hay cuentas con nombre 'Cuenta Migrada'.\n";
}

echo "¡Listo!\n";
