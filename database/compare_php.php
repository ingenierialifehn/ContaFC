<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();

$sqlFile = __DIR__ . '/fix_fechas.sql';
$lines = file($sqlFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$dbfDates = [];
foreach ($lines as $line) {
    if (preg_match("/fecha = '([^']+)' WHERE conteo = (\d+)/", $line, $matches)) {
        $dbfDates[(int)$matches[2]] = $matches[1];
    }
}

$stmt = $db->query("SELECT conteo, fecha FROM asientos WHERE conteo IS NOT NULL AND conteo > 0");
$dbDates = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $dbDates[(int)$row['conteo']] = $row['fecha'];
}

$diffs = 0;
$missingInDb = 0;
foreach ($dbfDates as $conteo => $fecha) {
    if (!isset($dbDates[$conteo])) {
        $missingInDb++;
        continue;
    }
    if ($dbDates[$conteo] !== $fecha) {
        echo "Diferencia en conteo $conteo: DBF=$fecha, DB=" . $dbDates[$conteo] . "\n";
        $diffs++;
    }
}

echo "Total diferencias de fechas: $diffs\n";
echo "Registros del DBF ausentes en la BD: $missingInDb\n";
