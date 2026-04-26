<?php
try {
    $db = new PDO("mysql:host=172.18.0.3;dbname=contafc", "root", "root");
    $codes = ['1002664322', '1021192323', '1037969539', '1050034305'];
    foreach ($codes as $code) {
        echo "--- Buscando transacciones para cuenta $code ---\n";
        $stmt = $db->prepare("
            SELECT a.descripcion, a.debito, a.credito, a.fecha, t.razon_social as tercero
            FROM asientos a
            JOIN puc_cuentas p ON a.cuenta_id = p.id
            LEFT JOIN terceros t ON a.tercero_id = t.id
            WHERE p.codigo = ?
            LIMIT 5
        ");
        $stmt->execute([$code]);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "Fecha: {$r['fecha']} | Desc: {$r['descripcion']} | D: {$r['debito']} | C: {$r['credito']} | Tercero: {$r['tercero']}\n";
        }
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
