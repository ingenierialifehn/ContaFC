<?php
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;

$_SESSION['user'] = ['id' => 1, 'rol' => 'admin'];
$_SESSION['empresa'] = 1;

$_GET['q'] = 'alca';
$_GET['tipo'] = 'cliente';

ob_start();
include __DIR__ . '/api/terceros.php';
$output = ob_get_clean();

file_put_contents(__DIR__ . '/debug_alca.json', $output);
echo "Debug for 'alca' saved.\n";
