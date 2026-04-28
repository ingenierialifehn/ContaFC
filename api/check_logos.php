<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;
$db = Database::getInstance()->getPdo();
$stmt = $db->query("SELECT logo_path FROM empresas WHERE id = 1");
echo "EMPRESA LOGO: " . $stmt->fetchColumn() . "\n";
$stmt = $db->query("SELECT logo_path FROM proyectos WHERE logo_path IS NOT NULL LIMIT 1");
echo "PROYECTO LOGO: " . $stmt->fetchColumn() . "\n";
?>
