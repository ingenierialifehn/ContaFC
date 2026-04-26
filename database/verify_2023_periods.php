<?php
$map = json_decode(file_get_contents(__DIR__ . '/period_map.json'), true);
if (isset($map[1][2023])) {
    echo "Periods for 2023 found.\n";
} else {
    echo "Periods for 2023 NOT found.\n";
}
