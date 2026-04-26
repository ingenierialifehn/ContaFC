<?php
require_once __DIR__ . '/../bootstrap.php';
$db = ContaFC\Core\Database::getInstance()->getPdo();
$sql = "UPDATE puc_cuentas SET naturaleza = CASE SUBSTR(codigo,1,1) 
            WHEN '1' THEN 'D' 
            WHEN '2' THEN 'C' 
            WHEN '3' THEN 'C' 
            WHEN '4' THEN 'C' 
            WHEN '5' THEN 'D' 
            WHEN '6' THEN 'D' 
            ELSE naturaleza 
        END";
$affected = $db->exec($sql);
echo "Affected rows: $affected\n";
