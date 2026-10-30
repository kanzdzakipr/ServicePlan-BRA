<?php
define('API_AUTH_OPTIONAL', true);
require_once __DIR__ . '/../api/db.php';
$pdo = Database::getInstance();

$splDir = __DIR__ . '/../material/DATA-SPL';
$files = [
    '01' => $splDir . '/01_SPL_Periode_21_Maret_20_April_2026.md',
    '02' => $splDir . '/02_SPL_Periode_21_Mei_20_Juni_2026.md',
    '03' => $splDir . '/03_SPL_Periode_21_Juni_20_Juli_2026.md',
    '04' => $splDir . '/04_SPL_Periode_21_Juli_20_Agustus_2026.md',
    '05' => $splDir . '/05_SPL_Periode_21_Agustus_20_September_2026.md',
];

$monthMap = [
    'januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04',
    'mei' => '05', 'juni' => '06', 'juli' => '07', 'agustus' => '08',
    'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12'
];

$employees = [];
$sheetList = [];
$unitMentions = [];
$timeDiscrepancies = [];

foreach ($files as $pCode => $fPath) {
    $content = file_get_contents($fPath);
    $lines = explode("\n", $content);
    $curSheet = null;
    $inRows = false;
    
    foreach ($lines as $line) {
        $trim = trim($line);
        if (preg_match('/^### Lembar SPL #(\d+): (.*)/i', $trim, $m)) {
            if ($curSheet) {
                $sheetList[] = $curSheet;
            }
            $curSheet = [
                'period' => $pCode,
                'sheet_idx' => (int)$m[1],
                'title' => $m[2],
                'date_str' => null,
                'date_iso' => null,
                'location' => null,
                'rows' => []
            ];
            $inRows = false;
            continue;
        }
        
        if ($curSheet) {
            if (preg_match('/- \*\*Tanggal SPL:\*\*\s*(.*)/i', $trim, $m)) {
                $curSheet['date_str'] = trim($m[1]);
                // Parse date string like "27 Maret 2026"
                if (preg_match('/(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})/', $curSheet['date_str'], $dm)) {
                    $mName = strtolower($dm[2]);
                    if (isset($monthMap[$mName])) {
                        $curSheet['date_iso'] = sprintf('%04d-%02d-%02d', $dm[3], $monthMap[$mName], $dm[1]);
                    }
                }
            } elseif (preg_match('/- \*\*Area \/ Lokasi Kerja:\*\*\s*(.*)/i', $trim, $m)) {
                $curSheet['location'] = trim($m[1]);
            }
            
            if (strpos($trim, '#### Tabulasi Rincian Pekerjaan Lembur') !== false) {
                $inRows = true;
                continue;
            }
            if (strpos($trim, '#### Pengesahan / Tanda Tangan Dokumen') !== false || (strpos($trim, '---') === 0 && $inRows)) {
                $inRows = false;
            }
            
            if ($inRows && strpos($trim, '|') === 0) {
                $cols = array_map('trim', explode('|', $trim));
                if (count($cols) >= 8 && is_numeric($cols[1])) {
                    $empName = str_replace(['**', '*'], '', $cols[2]);
                    $roleName = str_replace(['**', '*'], '', $cols[3]);
                    $job = $cols[4];
                    $start = $cols[5];
                    $end = $cols[6];
                    $reportedHours = (float)str_replace(['Jam', 'jam', ' '], '', $cols[7]);
                    
                    // Verify duration
                    $t1 = strtotime("2026-01-01 $start:00");
                    $t2 = strtotime("2026-01-01 $end:00");
                    if ($t2 < $t1) {
                        $t2 += 86400; // overnight
                    }
                    $calcHours = round(($t2 - $t1) / 3600, 2);
                    if (abs($calcHours - $reportedHours) > 0.05) {
                        $timeDiscrepancies[] = [
                            'sheet' => "P{$pCode}-#{$curSheet['sheet_idx']}",
                            'emp' => $empName,
                            'start' => $start,
                            'end' => $end,
                            'reported' => $reportedHours,
                            'calc' => $calcHours
                        ];
                    }
                    
                    $employees[$empName]['roles'][$roleName] = ($employees[$empName]['roles'][$roleName] ?? 0) + 1;
                    $employees[$empName]['total_hours'] = ($employees[$empName]['total_hours'] ?? 0) + $reportedHours;
                    $employees[$empName]['total_tasks'] = ($employees[$empName]['total_tasks'] ?? 0) + 1;
                    $employees[$empName]['periods'][$pCode] = true;
                    
                    $curSheet['rows'][] = [
                        'name' => $empName,
                        'role' => $roleName,
                        'job' => $job,
                        'start' => $start,
                        'end' => $end,
                        'hours' => $reportedHours
                    ];
                }
            }
        }
    }
    if ($curSheet) {
        $sheetList[] = $curSheet;
    }
}

echo "=== TIME DURATION DISCREPANCIES (CALCULATED VS REPORTED) ===\n";
echo "Count of discrepancies: " . count($timeDiscrepancies) . "\n";
foreach (array_slice($timeDiscrepancies, 0, 10) as $td) {
    echo "  {$td['sheet']} | {$td['emp']} | {$td['start']} - {$td['end']} | Reported: {$td['reported']} h | Calc: {$td['calc']} h\n";
}

echo "\n=== ALL 27 EMPLOYEES AUDIT ===\n";
arsort($employees);
$usersInDb = $pdo->query("SELECT user_id, username, full_name, role_id FROM users")->fetchAll(PDO::FETCH_ASSOC);
$rolesInDb = $pdo->query("SELECT role_id, role_name FROM roles")->fetchAll(PDO::FETCH_KEY_PAIR);

foreach ($employees as $name => $d) {
    $rolesStr = implode(', ', array_keys($d['roles']));
    $periodsStr = implode(', ', array_keys($d['periods']));
    
    // Find match in usersInDb
    $matchedUser = null;
    foreach ($usersInDb as $u) {
        $fn = strtolower(trim($u['full_name']));
        $un = strtolower(trim($u['username']));
        $target = strtolower(trim($name));
        
        // Exact or name contains
        if ($fn === $target || strpos($fn, $target) !== false || $un === $target || strpos($un, $target) !== false) {
            $matchedUser = $u;
            break;
        }
        // Handle aliases like Rejeki vs Rezeki, Rahmad Kamandanu vs rahmad_k, M All vs muhammad
        if (($target === 'rejeki siregar' && strpos($fn, 'rezeki') !== false) ||
            ($target === 'rahmad kamandanu' && $un === 'rahmad_k') ||
            (in_array($target, ['muhammad all', 'm all', 'muhamad all']) && strpos($un, 'all') !== false)) {
            $matchedUser = $u;
            break;
        }
    }
    
    if ($matchedUser) {
        $roleNameDb = $rolesInDb[$matchedUser['role_id']] ?? 'Unknown';
        printf("%-20s : %6.2f hrs (%2d tasks) [P: %-15s] -> MATCH DB user_id=%2d (%s, Role: %s)\n", 
            $name, $d['total_hours'], $d['total_tasks'], $periodsStr, $matchedUser['user_id'], $matchedUser['full_name'], $roleNameDb);
    } else {
        printf("%-20s : %6.2f hrs (%2d tasks) [P: %-15s] -> [!] MISSING FROM USERS TABLE (Role in SPL: %s)\n", 
            $name, $d['total_hours'], $d['total_tasks'], $periodsStr, $rolesStr);
    }
}

