<?php
try {
    $db = new PDO("mysql:host=172.18.0.3;dbname=contafc", "root", "root");
    $codes = ['1002664322', '1021192323', '1037969539', '1050034305'];
    foreach ($codes as $code) {
        $stmt = $db->prepare("SELECT a.descripcion, a.debito, a.credito, t.razon_social 
                              FROM asientos a 
                              LEFT JOIN terceros t ON a.tercero_id = t.id 
                              WHERE a.descripcion LIKE ? OR a.doc_cruce_num LIKE ?");
        $stmt->execute(["%$code%", "%$code%"]);
        while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "Encontrado $code -> {$r['razon_social']} | {$r['descripcion']}\n";
        }
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
