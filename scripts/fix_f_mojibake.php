<?php
function cleanFMojibake($filePath) {
    if (!file_exists($filePath)) return;
    $content = file_get_contents($filePath);
    $origContent = $content;

    $replacements = [
        'ƒÆ’ · ' => '&divide; ',
        'ƒÆ’ ·' => '&divide;',
        'ƒÆ’' => '&divide;',
        'ƒ¢‹Å“' => '[ ]',
    ];

    foreach ($replacements as $bad => $good) {
        $content = str_replace($bad, $good, $content);
    }

    if ($content !== $origContent) {
        file_put_contents($filePath, $content);
        echo "Cleaned F-mojibake in " . basename($filePath) . "\n";
    } else {
        echo "No F-mojibake found in " . basename($filePath) . "\n";
    }
}

cleanFMojibake(__DIR__ . '/../dashboard.html');
cleanFMojibake(__DIR__ . '/../scripts/dashboard.js');
