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

$data = json_decode($output, true);
echo "Count of results: " . count($data['data']) . "\n";
foreach($data['data'] as $r) {
    echo "ID: {$r['id']} | Result: {$r['razon_social']}\n";
}
