<?php
function cleanMojibake($filePath) {
    if (!file_exists($filePath)) return;
    $content = file_get_contents($filePath);
    $origContent = $content;

    // Targeted Mojibake map for JS and HTML
    $mojibakeMap = [
        ' - â€ ' => ' - ',
        ' - â€' => ' - ',
        '  - â€  ' => ' - ',
        ' - â€  ' => ' - ',
        ' - â€ ' => ' - ',
        'â€ ' => '',
        'â€' => '',
        '¢Ë†â€™' => '-',
        '¢â€ â€™' => '→',
        '¢â€ ' => '→',
        'Ã¢â‚¬â€œ' => ' - ',
        'Ã¢â‚¬â€”' => ' - ',
        'Ã¢â‚¬' => ' - ',
        'Ã‚Â·' => ' · ',
        'Ã‚Â' => '',
        'Ã¢â€°Â¤' => '≤',
        'Ã¢â€°Â¥' => '≥',
        'Ã¢Ë†âˆ’' => '-',
        'Ãƒâ€”' => '×',
        'ÃƒÆ’Ã‚Â·' => '÷',
        'Ã¢Å¡Â Ã¯Â¸Â ' => '⚠️ ',
        'Ã¢Å¡Â ' => '⚠️ ',
        'Ã¢Å“â€¦' => '✅ ',
        ' -œ ' => ' - ',
        '-œ' => '-',
        '- "™' => ' &rarr; ',
        '¢— ' => '• ',
        '¢—' => '•',
        'Â·' => '·',
        'Â' => '',
        'Ã' => '',
        'ï¿½' => ''
    ];

    foreach ($mojibakeMap as $bad => $good) {
        $content = str_replace($bad, $good, $content);
    }

    if ($content !== $origContent) {
        file_put_contents($filePath, $content);
        echo "Cleaned Mojibake in " . basename($filePath) . "\n";
    } else {
        echo "No Mojibake changes needed for " . basename($filePath) . "\n";
    }
}

cleanMojibake(__DIR__ . '/../dashboard.html');
cleanMojibake(__DIR__ . '/../scripts/dashboard.js');
