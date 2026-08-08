<?php
$jsPath = __DIR__ . '/../scripts/dashboard.js';
$lines = explode("\n", file_get_contents($jsPath));

$paren = 0;
$curly = 0;
$square = 0;

$issues = [];

foreach ($lines as $i => $line) {
    $lineNum = $i + 1;
    $len = strlen($line);

    // Ignore single line comments starting with //
    $codeLine = preg_replace('/\/\/.*$/', '', $line);

    for ($j = 0; $j < strlen($codeLine); $j++) {
        $c = $codeLine[$j];
        if ($c === '(') $paren++;
        elseif ($c === ')') {
            $paren--;
            if ($paren < 0) {
                $issues[] = "Line $lineNum: Unmatched closing parenthesis ')'! Code: " . trim($line);
                $paren = 0; // reset to avoid cascade
            }
        }
        elseif ($c === '{') $curly++;
        elseif ($c === '}') {
            $curly--;
            if ($curly < 0) {
                $issues[] = "Line $lineNum: Unmatched closing brace '}'! Code: " . trim($line);
                $curly = 0;
            }
        }
        elseif ($c === '[') $square++;
        elseif ($c === ']') {
            $square--;
            if ($square < 0) {
                $issues[] = "Line $lineNum: Unmatched closing bracket ']'! Code: " . trim($line);
                $square = 0;
            }
        }
    }
}

echo "Detected Bracket Issues in dashboard.js:\n";
echo "========================================================================================\n";
foreach ($issues as $issue) {
    echo "$issue\n";
}
echo "========================================================================================\n";
echo "End of analysis. Final unclosed counts: paren=$paren, curly=$curly, square=$square\n";
