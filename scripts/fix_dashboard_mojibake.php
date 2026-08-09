<?php
$filePath = __DIR__ . '/../dashboard.view.php';
$content = file_get_contents($filePath);

// Clean non-breaking space bytes (\xA0 / \x00\xA0 / \xC2\xA0)
$content = preg_replace('/\x{00A0}/u', ' ', $content);
$content = str_replace("\xA0", " ", $content);
$content = str_replace("-  ", "- ", $content);

file_put_contents($filePath, $content);
echo "Successfully cleaned double spaces and non-breaking space bytes in dashboard.html!\n";
