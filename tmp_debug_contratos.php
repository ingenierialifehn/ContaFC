<?php
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;
use ContaFC\Core\Auth;

$_SESSION['user'] = ['id' => 1, 'rol' => 'admin'];
$_SESSION['empresa'] = 1;

ob_start();
require __DIR__ . '/api/com-contratos.php';
$output = ob_get_clean();

file_put_contents(__DIR__ . '/tmp_contratos_debug.json', $output);
echo "Output saved to tmp_contratos_debug.json\n";
