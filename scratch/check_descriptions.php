<?php
require_once __DIR__ . '/../bootstrap.php';
use ContaFC\Core\Database;

$db = Database::getInstance()->getPdo();

$queries = [
    "Intereses Financiacion",
    "Intereses Financiación"
];

foreach ($queries as $q) {
    echo "Searching for: '$q'\n";
    $stmt = $db->prepare("SELECT a.id, a.descripcion, c.numero, tc.codigo as tipo 
                         FROM asientos a 
                         JOIN comprobantes c ON a.comprobante_id = c.id
                         JOIN tipos_comprobante tc ON c.tipo_comp_id = tc.id
                         WHERE a.descripcion LIKE :q");
    $stmt->execute([':q' => "%$q%"]);
    $results = $stmt->fetchAll();
    echo "Found: " . count($results) . "\n";
    foreach ($results as $row) {
        echo " - ID: {$row['id']} | Desc: {$row['descripcion']} | Comp: {$row['tipo']}-{$row['numero']}\n";
    }
    echo "-------------------\n";
}
