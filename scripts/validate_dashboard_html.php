<?php
$filePath = __DIR__ . '/../dashboard.view.php';
$content = file_get_contents($filePath);

// Check unclosed tags or duplicate script calls
echo "Checking dashboard.html integrity...\n";

$scripts = [];
if (preg_match_all('/<script [^>]*src=["\']([^"\']+)["\']/', $content, $m)) {
    echo "Found script tags:\n";
    foreach ($m[1] as $s) {
        echo " - $s\n";
    }
}

// Check for conflict markers in html
if (strpos($content, '<<<<<<<') !== false || strpos($content, '=======') !== false || strpos($content, '>>>>>>>') !== false) {
    echo "WARNING: Git conflict markers found in dashboard.html!\n";
} else {
    echo "No Git conflict markers in dashboard.html.\n";
}
