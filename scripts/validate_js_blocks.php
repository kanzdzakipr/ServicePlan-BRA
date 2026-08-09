<?php
$htmlPath = __DIR__ . '/../dashboard.view.php';
$jsPath = __DIR__ . '/../scripts/dashboard.js';

function checkBrackets($filePath) {
    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);
    
    $curly = 0;
    $paren = 0;
    $square = 0;

    foreach ($lines as $i => $line) {
        $len = strlen($line);
        for ($j = 0; $j < $len; $j++) {
            $char = $line[$j];
            if ($char === '{') $curly++;
            elseif ($char === '}') $curly--;
            elseif ($char === '(') $paren++;
            elseif ($char === ')') $paren--;
            elseif ($char === '[') $square++;
            elseif ($char === ']') $square--;
        }
        if ($curly < 0 || $paren < 0 || $square < 0) {
            echo basename($filePath) . " [Line " . ($i + 1) . "]: Negative bracket count (curly:$curly, paren:$paren, square:$square) -> " . trim($line) . "\n";
            return;
        }
    }

    echo basename($filePath) . " final bracket balance: curly=$curly, paren=$paren, square=$square\n";
}

checkBrackets($htmlPath);
checkBrackets($jsPath);
