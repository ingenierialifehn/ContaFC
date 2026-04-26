<?php
require_once __DIR__ . '/bootstrap.php';
$db = \ContaFC\Core\Database::getInstance()->getPdo();
$q = $db->query("DESCRIBE asientos");
while($r = $q->fetch(PDO::FETCH_ASSOC)) {
    echo $r['Field'] . " (" . $r['Type'] . ")\n";
}
echo "---\n";
$q = $db->query("DESCRIBE comprobantes");
while($r = $q->fetch(PDO::FETCH_ASSOC)) {
    echo $r['Field'] . " (" . $r['Type'] . ")\n";
}
