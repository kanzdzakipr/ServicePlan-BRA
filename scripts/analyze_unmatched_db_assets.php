<?php
declare(strict_types=1);

require_once __DIR__ . '/audit_rekap_normalization_matcher.php';

$pdo = new PDO('mysql:host=127.0.0.1;dbname=u646470441_ServicePlanBRA;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$dbAssets = $pdo->query("
    SELECT asset_id, asset_code, hull_number, category, make_model,
           year_manufacture, serial_number, license_plate, current_location_id, status
    FROM assets
    WHERE is_active = 1
    ORDER BY category ASC, asset_code ASC
")->fetchAll();

// Load the generated JSON from earlier run
$jsonPath = __DIR__ . '/../reports/normalization_pilot_candidates.json';
$reportData = json_decode(file_get_contents($jsonPath), true);

// Re-run matcher to get the noEvidence and review lists in detail
$matcher = new AssetNormalizationMatcher();
// We can use reflection to access the private properties or re-evaluate
$reflection = new ReflectionClass($matcher);
$matcher->run();

$propNoEvidence = $reflection->getProperty('noEvidence');
$propNoEvidence->setAccessible(true);
$noEvidenceAssets = $propNoEvidence->getValue($matcher);

$propMatchReview = $reflection->getProperty('matchReview');
$propMatchReview->setAccessible(true);
$matchReviewAssets = $propMatchReview->getValue($matcher);

$propConflicts = $reflection->getProperty('conflicts');
$propConflicts->setAccessible(true);
$conflictCases = $propConflicts->getValue($matcher);

echo "\n=============================================================\n";
echo " ANALISIS UNIT MASTER ASSET YANG BELUM COCOK DENGAN REKAP 2026\n";
echo "=============================================================\n";

// 1. Group NO_EVIDENCE by category
$noEvidenceByCategory = [];
foreach ($noEvidenceAssets as $a) {
    $cat = $a['category'] ?: 'Tidak Berkategori';
    $noEvidenceByCategory[$cat][] = $a;
}

echo "\n--- 1. SEBARAN 120 UNIT DB 'NO_EVIDENCE' (Sama sekali belum ada di REKAP 2026) ---\n";
foreach ($noEvidenceByCategory as $cat => $units) {
    echo sprintf(" - %-20s : %3d unit\n", $cat, count($units));
}

echo "\nContoh unit per kategori yang belum ditemukan di REKAP 2026:\n";
foreach ($noEvidenceByCategory as $cat => $units) {
    $sampleCodes = array_map(fn($u) => $u['asset_code'] . ($u['license_plate'] ? " [{$u['license_plate']}]" : ""), array_slice($units, 0, 5));
    echo "   * $cat: " . implode(', ', $sampleCodes) . (count($units) > 5 ? " (+" . (count($units) - 5) . " lainnya)" : "") . "\n";
}

// 2. CONFLICTS details
echo "\n--- 2. DAFTAR 5 KASUS KONFLIK (Ambigu / Klaim Ganda) ---\n";
foreach ($conflictCases as $i => $c) {
    $r = $c['rekap'];
    $dbUnits = array_map(fn($a) => "{$a['asset_code']} ({$a['category']})", $c['candidate_assets']);
    echo sprintf(" [%d] Baris %s: %s | SN/Pelat: %s\n     -> Bentrok ke DB: %s\n",
        $i + 1,
        $r['row_num'],
        $r['nama_asset'],
        $r['raw_sn_plate'],
        implode(' vs ', $dbUnits)
    );
}

// 3. MATCH_REVIEW details
echo "\n--- 3. DAFTAR 15 UNIT 'MATCH_REVIEW' (Skor 80-94, Butuh Verifikasi) ---\n";
foreach ($matchReviewAssets as $mr) {
    $a = $mr['asset'];
    $r = $mr['rekap'];
    echo sprintf(" * DB: %-12s (%-16s) <-> REKAP Baris %-3s: %-15s (Skor %d)\n   Model DB: %s | REKAP: %s\n",
        $a['asset_code'],
        $a['category'],
        $r['row_num'],
        $r['kode_unit'] ?: $r['lambung'],
        $mr['score'],
        $a['make_model'] ?: '(kosong)',
        $r['nama_asset']
    );
}

