<?php
require_once 'bootstrap.php';
$dbHost = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: 'contafc_db');
$dbName = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'contafc');
$dbUser = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'contafc_user');
$dbPass = defined('DB_PASSWORD') ? DB_PASSWORD : (getenv('DB_PASSWORD') ?: '');

$backupDir = __DIR__ . '/backups';
$filename = 'backup_' . date('Ymd_His') . '.sql';
$path = $backupDir . '/' . $filename;

$cmd = sprintf(
    'mysqldump -h %s -u %s --password=%s %s > %s 2>&1',
    escapeshellarg($dbHost),
    escapeshellarg($dbUser),
    escapeshellarg($dbPass),
    escapeshellarg($dbName),
    escapeshellarg($path)
);

echo "Executing: $cmd\n";
exec($cmd, $output, $returnVar);

echo "ReturnVar: $returnVar\n";
echo "Output: \n" . implode("\n", $output) . "\n";
