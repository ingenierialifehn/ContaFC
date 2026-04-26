<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$res = $db->query("SELECT logo_path FROM empresas WHERE id = 1")->fetchColumn();
echo "Empresa logo: $res\n";
