<?php
$url = "http://localhost:8082/contafc/api/libros_oficiales.php?tipo=INVENTARIOS&pid=2023&subtipo=capital&folio=1";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// Mock session if needed? No, curl won't have session unless we pass cookie.
// But I can't easily get the session cookie from here.

// I will use a script that sets the session and then redirects? No.
// I will use a script that sets the session and then includes the file.
// That's what simulate_api_v2.php does.
