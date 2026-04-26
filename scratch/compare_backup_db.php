<?php
require_once __DIR__ . '/../bootstrap.php';
$pdo = \ContaFC\Core\Database::getInstance()->getPdo();

$backupPath = __DIR__ . '/../backups/backup_20260406_104035.sql';
$protected_codes = ['11050101', '110301', '11020101'];

// Phase 1: Local Map
$stmt = $pdo->query("SELECT c.codigo, COUNT(*) as qty FROM asientos a JOIN puc_cuentas c ON a.cuenta_id = c.id WHERE a.comprobante_id = 9999 GROUP BY c.codigo");
$dbCounts = [];
while ($row = $stmt->fetch()) $dbCounts[$row['codigo']] = $row['qty'];

// Phase 2: Backup Map
$pucMap = [];
$handle = fopen($backupPath, "r");
while (($line = fgets($handle)) !== false) {
    if (strpos($line, "INSERT INTO `puc_cuentas` VALUES") !== false) {
        $part = substr($line, strpos($line, "VALUES ") + 7);
        $records = explode("),", $part);
        foreach ($records as $r) {
            $f = str_getcsv(trim($r, " ()\r\n;"), ",", "'");
            if (count($f) >= 3) $pucMap[$f[0]] = $f[2];
        }
    }
}
rewind($handle);

$backupCounts = [];
while (($line = fgets($handle)) !== false) {
    if (strpos($line, "INSERT INTO `asientos` VALUES") !== false) {
        $part = substr($line, strpos($line, "VALUES ") + 7);
        $records = explode("),", $part);
        foreach ($records as $r) {
            $f = str_getcsv(trim($r, " ()\r\n;"), ",", "'");
            if (count($f) >= 5) {
                $code = $pucMap[$f[4]] ?? "UNK";
                if (!in_array($code, $protected_codes)) {
                    $backupCounts[$code] = ($backupCounts[$code] ?? 0) + 1;
                }
            }
        }
    }
}
fclose($handle);

echo sprintf("%-15s | %-10s | %-10s | %s\n", "Codigo", "Backup", "DB", "Diff");
echo str_repeat("-", 50) . "\n";

$allCodes = array_unique(array_merge(array_keys($backupCounts), array_keys($dbCounts)));
sort($allCodes);

$totalMissing = 0;
foreach ($allCodes as $code) {
    $b = $backupCounts[$code] ?? 0;
    $d = $dbCounts[$code] ?? 0;
    if ($b > $d) {
        $diff = $b - $d;
        echo sprintf("%-15s | %-10d | %-10d | %d\n", $code, $b, $d, $diff);
        $totalMissing += $diff;
    }
}
echo str_repeat("-", 50) . "\n";
echo "TOTAL MISSING RECORDS: $totalMissing\n";
