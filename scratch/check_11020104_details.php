<?php
$backupPath = __DIR__ . '/../backups/backup_20260406_104035.sql';
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
$deb = 0; $cre = 0; $count = 0;
while (($line = fgets($handle)) !== false) {
    if (strpos($line, "INSERT INTO `asientos` VALUES") !== false) {
        $part = substr($line, strpos($line, "VALUES ") + 7);
        $records = explode("),", $part);
        foreach ($records as $r) {
            $f = str_getcsv(trim($r, " ()\r\n;"), ",", "'");
            if (count($f) >= 27) {
                $code = $pucMap[$f[4]] ?? '';
                if ($code == '11020104' && $f[26] <= '2023-12-31') {
                    $count++;
                    $deb += (float)$f[8];
                    $cre += (float)$f[9];
                }
            }
        }
    }
}
fclose($handle);
echo "11020104 in backup (to 2023): $count records. Total Deb: $deb. Total Cre: $cre. Bal: " . ($deb - $cre) . "\n";
