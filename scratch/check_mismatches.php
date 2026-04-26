<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();

echo "Mismatched Empresa IDs:\n";

$sql1 = "SELECT COUNT(*) FROM asientos a JOIN puc_cuentas p ON a.cuenta_id = p.id WHERE a.empresa_id <> p.empresa_id";
$mismatch1 = $db->query($sql1)->fetchColumn();
echo "Asientos <> PUC: $mismatch1\n";

$sql2 = "SELECT COUNT(*) FROM asientos a JOIN comprobantes c ON a.comprobante_id = c.id WHERE a.empresa_id <> c.empresa_id";
$mismatch2 = $db->query($sql2)->fetchColumn();
echo "Asientos <> Comprobantes: $mismatch2\n";

$sql3 = "SELECT COUNT(*) FROM comprobantes c JOIN empresas e ON c.empresa_id = e.id WHERE 1=0"; // Just testing
echo "Check finished.\n";
