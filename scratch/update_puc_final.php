<?php
$mapping = [
    "312256640" => "ESAU SALOMON GARCIA ARGUETA",
    "329033856" => "ESAU SALOMON GARCIA ARGUETA",
    "873735810" => "ERIKA CAROLINA VELASQUEZ MEZA",
    "902001026" => "MAYRA LUDIS SUAZO VASQUEZ",
    "903751811" => "MAYRA LUDIS SUAZO VASQUEZ",
    "904962691" => "SINTIA YAMILETH ANARIBA RAMOS",
    "915816577" => "SINTIA YAMILETH ANARIBA RAMOS",
    "917027457" => "SINTIA YAMILETH ANARIBA RAMOS",
    "918778242" => "KINVERLY MICHELL LEONARDO VINDEL",
    "935555458" => "MARIA ESTER BONILLA SORIANO",
    "949371009" => "LESTHER PAUL DUBON BUEZO",
    "950581889" => "GLADYS ELIZABETH ALFARO EUCEDA",
    "952332674" => "EDGARDO ULISES DIAZ FLORES",
    "1002664322" => "JOSE  ARMANDO VILLANUEVA MORALES",
    "1021192323" => "ALEXIS ORLANDO MALDONADO RODRIGUEZ",
    "1037969539" => "MARIO GUILLERMO SOLORZANO ALVARADO",
    "1050034305" => "KIMBERLY NICOL VALLE BARAHONA",
    "1138632835" => "FAVIO ENRIQUE SOLORZANO ALVARADO",
    "1222518915" => "ESAU SALOMON GARCIA ARGUETA",
    "1239296131" => "LEONEL ALFREDO CRUZ FLORES",
    "1256073347" => "JOSE ANTONIO AGUILAR",
    "1289627779" => "LUZ MARIA RODRIGUEZ MORENO",
    "1457399939" => "LUZ MARIA RODRIGUEZ MORENO",
    "1490954371" => "NANCY JULISSA CASTILLO FORTIN",
    "1558063235" => "VELKIS DEL CARMEN FIALLOS TORRES",
    "1574840451" => "ERIKA JOSSELYN DONAIRE GALEAS",
    "1641949315" => "MARIA AUDELINA MONTESINOS AGUILAR",
    "1965045891" => "CARMEN ISABEL MAYORQUIN ESTRADA",
    "1977715841" => "JOSE ISAIAS ULLOA HERNANDEZ",
    "1980072322" => "JOSE  ARMANDO VILLANUEVA MORALES"
];

try {
    $db = new PDO("mysql:host=172.18.0.3;dbname=contafc", "root", "root");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Actualizando nombres de cuentas de clientes...\n";
    $stmt = $db->prepare("UPDATE puc_cuentas SET nombre = ?, naturaleza = ?, tipo_cuenta = ? WHERE codigo = ? AND empresa_id = 1");

    foreach ($mapping as $codigo => $nombre) {
        // Determinamos naturaleza y tipo basado en el primer dígito
        $naturaleza = 'D';
        $tipo = 'A';
        $primer_digito = substr($codigo, 0, 1);
        
        if ($primer_digito == '2') {
            $naturaleza = 'C';
            $tipo = 'P';
        } elseif ($primer_digito == '3') {
            $naturaleza = 'C';
            $tipo = 'R';
        }

        $stmt->execute([$nombre, $naturaleza, $tipo, $codigo]);
        echo "Actualizado: $codigo -> $nombre\n";
    }

    echo "\nProceso completado.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
