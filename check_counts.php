<?php
require_once __DIR__ . '/bootstrap.php';
use ContaFC\Core\Database;

try {
    $db = Database::getInstance()->getPdo();
    $compCount = $db->query("SELECT count(*) FROM comprobantes")->fetchColumn();
    $asientoCount = $db->query("SELECT count(*) FROM asientos")->fetchColumn();
    $distinctCompInAsientos = $db->query("SELECT count(distinct comprobante_id) FROM asientos")->fetchColumn();
    $empresas = $db->query("SELECT id, nombre FROM empresas")->fetchAll(PDO::FETCH_ASSOC);

    echo "Comprobantes Total: $compCount\n";
    echo "Asientos Total: $asientoCount\n";
    echo "Distinct Comprobantes in Asientos: $distinctCompInAsientos\n";
    echo "Empresas:\n";
    print_r($empresas);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
