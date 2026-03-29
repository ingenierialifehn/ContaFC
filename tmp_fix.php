<?php
$files = glob('*.php');
foreach ($files as $file) {
    if (in_array($file, ['index.php', 'login.php', 'logout.php', 'bootstrap.php'])) continue;

    $content = file_get_contents($file);

    // Bootstrap require
    $content = str_replace("require_once __DIR__ . '/../bootstrap.php';", "require_once __DIR__ . '/bootstrap.php';", $content);

    // Sidebar replacement
    $pattern = '/<aside[^>]*>.*?<\/aside>/s';
    $replacement = "<?php \$activeNav = '" . pathinfo($file, PATHINFO_FILENAME) . "'; require __DIR__ . '/partials/sidebar.php'; ?>";
    $content = preg_replace($pattern, $replacement, $content);

    // JS fetch URLs
    $content = str_replace("fetch(`/api/", "fetch(`<?= BASE_URL ?>/api/", $content);
    $content = str_replace("fetch('/api/", "fetch('<?= BASE_URL ?>/api/", $content);

    // HREF Links
    $content = str_replace('href="/comprobantes.php', 'href="<?= BASE_URL ?>/comprobantes.php', $content);
    $content = str_replace('href="/asiento.php', 'href="<?= BASE_URL ?>/asiento.php', $content);
    
    // JS Links
    $content = str_replace('href="/asiento.php?id=${r.id}"', 'href="<?= BASE_URL ?>/asiento.php?id=${r.id}"', $content);

    file_put_contents($file, $content);
    echo "Fixed $file\n";
}
