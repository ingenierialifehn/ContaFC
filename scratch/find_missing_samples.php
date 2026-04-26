<?php
require_once __DIR__ . '/../bootstrap.php';
$pdo = \ContaFC\Core\Database::getInstance()->getPdo();

$backupPath = __DIR__ . '/../backups/backup_20260406_104035.sql';
$protected_codes = ['11050101', '110301', '11020101'];

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

echo "Checking for missing records...\n";
$missing = [];
while (($line = fgets($handle)) !== false) {
    if (strpos($line, "INSERT INTO `asientos` VALUES") !== false) {
        $part = substr($line, strpos($line, "VALUES ") + 7);
        $records = explode("),", $part);
        foreach ($records as $r) {
            $f = str_getcsv(trim($r, " ()\r\n;"), ",", "'");
            if (count($f) >= 27) {
                $code = $pucMap[$f[4]] ?? "UNK";
                if (!in_array($code, $protected_codes)) {
                    $linea = (int)$f[3];
                    $conteo = ($f[25] === 'NULL') ? null : (int)$f[25];
                    
                    // Check if exists in DB
                    $exists = false;
                    if ($conteo !== null) {
                        $stmt = $pdo->prepare("SELECT id FROM asientos WHERE conteo = ?");
                        $stmt->execute([$conteo]);
                        if ($stmt->fetch()) $exists = true;
                    } else {
                        $stmt = $pdo->prepare("SELECT id FROM asientos WHERE comprobante_id = 9999 AND linea = ?");
                        $stmt->execute([$linea]);
                        if ($stmt->fetch()) $exists = true;
                    }
                    
                    if (!$exists) {
                        $missing[] = [
                            'code' => $code,
                            'linea' => $linea,
                            'conteo' => $conteo,
                            'fecha' => $f[26]
                        ];
                        if (count($missing) >= 10) break 2;
                    }
                }
            }
        }
    }
}
fclose($handle);

print_r($missing);
