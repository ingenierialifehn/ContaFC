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
$count = 0;
while (($line = fgets($handle)) !== false) {
    if (strpos($line, "INSERT INTO `asientos` VALUES") !== false) {
        $part = substr($line, strpos($line, "VALUES ") + 7);
        $records = explode("),", $part);
        foreach ($records as $r) {
            $f = str_getcsv(trim($r, " ()\r\n;"), ",", "'");
            if (count($f) >= 27) {
                $code = $pucMap[$f[4]] ?? '';
                if ($code == '11020109') $count++;
            }
        }
    }
}
fclose($handle);
echo "Total 11020109 records in backup: $count\n";
