<?php
require_once __DIR__ . '/../bootstrap.php';
$pdo = \ContaFC\Core\Database::getInstance()->getPdo();

$backupPath = __DIR__ . '/../backups/backup_20260406_104035.sql';

// Get current accounts
$stmt = $pdo->query("SELECT codigo FROM puc_cuentas");
$existingCodes = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Map backup IDs to codes
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

echo "Accounts in backup NOT in current system:\n";
$missing = [];
while (($line = fgets($handle)) !== false) {
    if (strpos($line, "INSERT INTO `asientos` VALUES") !== false) {
        $part = substr($line, strpos($line, "VALUES ") + 7);
        $records = explode("),", $part);
        foreach ($records as $r) {
            $f = str_getcsv(trim($r, " ()\r\n;"), ",", "'");
            if (count($f) >= 5) {
                $code = $pucMap[$f[4]] ?? "UNK";
                if ($code !== "UNK" && !in_array($code, $existingCodes)) {
                    $missing[$code] = ($missing[$code] ?? 0) + 1;
                }
            }
        }
    }
}
fclose($handle);

arsort($missing);
foreach ($missing as $code => $qty) {
    echo "$code: $qty records\n";
}
if (empty($missing)) echo "None found.\n";
