<?php
declare(strict_types=1);

/**
 * Audit & Staging Matcher for Master Asset Normalization
 * 
 * Implements Stage 2 & 3 of RENCANA_STRATEGIS_NORMALISASI_KELENGKAPAN_MASTER_ASSET.md
 * and PEMBELAJARAN_KONSEPTUAL_NORMALISASI_MASTER_ASSET.md.
 * 
 * READ-ONLY: Does not modify any database table.
 */

require_once __DIR__ . '/../core/AssetIdentity.php';

final class AssetNormalizationMatcher
{
    private PDO $db;
    private array $dbAssets = [];
    private array $rekapRows = [];
    private array $mutasiLookup = [];
    private array $komtraxLookup = [];

    // Classification buckets
    private array $matchAuto = [];
    private array $matchReview = [];
    private array $conflicts = [];
    private array $newCandidates = [];
    private array $noEvidence = [];

    public function __construct()
    {
        $this->connectDb();
    }

    private function connectDb(): void
    {
        $host = '127.0.0.1';
        $port = '3306';
        $database = 'u646470441_ServicePlanBRA';
        $username = 'root';
        $password = '';

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        $this->db = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public function run(): void
    {
        echo "====================================================================\n";
        echo " ServicePlan-BRA: Master Asset Normalization Matcher & Staging Audit\n";
        echo " Baseline: 23 Oktober 2026 (Read-Only Mode)\n";
        echo "====================================================================\n\n";

        $this->loadDbAssets();
        $this->loadMutasiLookup();
        $this->loadKomtraxLookup();
        $this->loadRekap2026();
        $this->executeMatching();
        $this->printConsoleSummary();
        $this->generateMarkdownReport();
        $this->generateJsonExport();
    }

    private function loadDbAssets(): void
    {
        echo "[1/4] Loading active assets from database (u646470441_ServicePlanBRA)...\n";
        $stmt = $this->db->query("
            SELECT asset_id, asset_code, hull_number, category, make_model,
                   year_manufacture, serial_number, license_plate, previous_license_plate,
                   chassis_number, dealer, capacity, project_name, operational_position,
                   current_location_id, status, is_active
            FROM assets
            WHERE is_active = 1
            ORDER BY asset_id ASC
        ");
        $this->dbAssets = $stmt->fetchAll();
        echo "      Loaded " . count($this->dbAssets) . " active units.\n";
    }

    private function loadMutasiLookup(): void
    {
        $path = __DIR__ . '/../material/ASSET_REKAP_MUTASI_UNIT_DURI_sheet_REKAP.md';
        if (!file_exists($path)) {
            echo "      [WARN] Mutasi file not found: $path\n";
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!str_starts_with($line, '|')) continue;
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) < 9) continue;
            // No(1) | Tahap(2) | Nopol(3) | Jenis(4) | Proyek(5) | Selesai Mutasi(6) | Nopol Baru(7) | Keterangan(8)
            $oldPlate = $this->cleanPlate($parts[3]);
            $isDone = strtoupper($parts[6]) === 'TRUE';
            $newPlate = $this->cleanPlate($parts[7]);
            $ket = $parts[8] ?? '';

            if ($oldPlate !== '') {
                $this->mutasiLookup[$oldPlate] = [
                    'old_plate' => $oldPlate,
                    'new_plate' => $newPlate,
                    'is_done' => $isDone,
                    'status_desc' => $ket,
                    'proyek' => $parts[5] ?? '',
                    'jenis' => $parts[4] ?? ''
                ];
            }
        }
        echo "      Loaded " . count($this->mutasiLookup) . " plate mutation records.\n";
    }

    private function loadKomtraxLookup(): void
    {
        $path = __DIR__ . '/../material/AVAILABILITY_UNIT/20_BRA_KOMTRAX_Januari_2026.md';
        if (!file_exists($path)) return;

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!str_starts_with($line, '|')) continue;
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) < 8) continue;
            // No(1) | Model(2) | Type(3) | Serial No.(4) | Customer Machine No.(5) | Sub Group(6) | SMR(7)
            $sn = strtoupper(trim($parts[4]));
            if ($sn !== '' && $sn !== 'SERIAL NO.' && !str_starts_with($sn, '---')) {
                $this->komtraxLookup[$sn] = [
                    'model' => $parts[2],
                    'type' => $parts[3],
                    'sn' => $sn,
                    'sub_group' => $parts[6],
                    'smr' => $parts[7]
                ];
            }
        }
        echo "      Loaded " . count($this->komtraxLookup) . " KOMTRAX telematics unit records.\n";
    }

    private function loadRekap2026(): void
    {
        echo "[2/4] Parsing REKAP HEAVY EQUIPMENT BRA 2026...\n";
        $path = __DIR__ . '/../REKAP HEAVY EQUIPMENT BRA 2026 - List Data Aset BRA.md';
        if (!file_exists($path)) {
            throw new RuntimeException("REKAP file not found at: $path");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        $headerFound = false;
        $rowIdx = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (!str_starts_with($line, '|')) continue;

            $cols = array_map('trim', explode('|', $line));
            // Shift off empty boundary elements from markdown table '| ... |'
            if (isset($cols[0]) && $cols[0] === '') array_shift($cols);
            if (!empty($cols) && end($cols) === '') array_pop($cols);

            if (count($cols) < 15) continue;

            $noVal = trim($cols[0]);
            if ($noVal === 'No') {
                $headerFound = true;
                continue;
            }
            if (!$headerFound || str_starts_with($noVal, '---')) continue;

            // Skip Category grouping header rows like 'A', 'B', 'C', 'D'
            if (preg_match('/^[A-Z]$/', $noVal) && empty(trim($cols[3] ?? ''))) {
                continue;
            }

            $rowIdx++;
            // Map columns:
            // 0: No, 1: Kolom B, 2: Nama Asset, 3: Dealer, 4: Tahun, 5: Serial Number/Pelat,
            // 6: Status Warranty, 7: Kapasitas, 8: Kode Unit, 9: Nomor Lambung Aktual,
            // 10: PROJECT, 11: LOKASI, 12: Status, 13: LOKASI INVOICE, 14: STATUS LUNAS/LEASING
            // 19: USIA, 20: Kategori Clean
            $rawSnPlate = trim($cols[5] ?? '');
            $kodeUnit = trim($cols[8] ?? '');
            $lambung = trim($cols[9] ?? '');
            $namaAsset = trim($cols[2] ?? '');
            $dealer = trim($cols[3] ?? '');
            $tahun = trim($cols[4] ?? '');
            $warranty = trim($cols[6] ?? '');
            $kapasitas = trim($cols[7] ?? '');
            $project = trim($cols[10] ?? '');
            $lokasi = trim($cols[11] ?? '');
            $status = trim($cols[12] ?? '');
            $kategoriClean = trim($cols[20] ?? ($cols[1] ?? ''));

            // Parse compound SN / Plate token
            $tokens = $this->parseCompoundToken($rawSnPlate);

            $this->rekapRows[] = [
                'row_num' => $noVal,
                'source_row_index' => $rowIdx,
                'nama_asset' => $namaAsset,
                'dealer' => $dealer,
                'tahun' => $tahun,
                'raw_sn_plate' => $rawSnPlate,
                'tokens' => $tokens,
                'warranty' => $warranty,
                'kapasitas' => $kapasitas,
                'kode_unit' => $kodeUnit,
                'lambung' => $lambung,
                'project' => $project,
                'lokasi' => $lokasi,
                'status' => $status,
                'kategori_clean' => $kategoriClean,
                'matched_asset_id' => null
            ];
        }

        echo "      Parsed " . count($this->rekapRows) . " equipment data rows from REKAP 2026.\n";
    }

    private function parseCompoundToken(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '' || $raw === '-' || $raw === 'N/A') {
            return [];
        }

        // Split on slash or double space
        $rawTokens = preg_split('/\s*\/\s*/', $raw);
        $results = [];

        foreach ($rawTokens as $tok) {
            $tok = trim($tok);
            if ($tok === '') continue;

            $cleaned = strtoupper($tok);
            // Detect if looks like Indonesian license plate:
            // e.g. BM 8737 DO, B 9102 ZYT, DT 8126 JE, BA 9016 QU, B9107ZYT
            $compact = preg_replace('/\s+/', '', $cleaned);
            if (preg_match('/^([A-Z]{1,2})(\d{1,4})([A-Z]{1,3})$/', $compact, $m)) {
                $formattedPlate = $m[1] . ' ' . $m[2] . ' ' . $m[3];
                $results[] = [
                    'raw' => $tok,
                    'clean' => $formattedPlate,
                    'compact' => $compact,
                    'type' => 'PLATE'
                ];
            } else {
                // Serial Number or Chassis
                $results[] = [
                    'raw' => $tok,
                    'clean' => $cleaned,
                    'compact' => preg_replace('/[^A-Z0-9]/', '', $cleaned),
                    'type' => 'SN_OR_CHASSIS'
                ];
            }
        }

        return $results;
    }

    private function cleanPlate(string $plate): string
    {
        $p = strtoupper(trim($plate));
        $compact = preg_replace('/\s+/', '', $p);
        if (preg_match('/^([A-Z]{1,2})(\d{1,4})([A-Z]{1,3})$/', $compact, $m)) {
            return $m[1] . ' ' . $m[2] . ' ' . $m[3];
        }
        return $p;
    }

    private function executeMatching(): void
    {
        echo "[3/4] Running multi-signal identity resolution and confidence scoring...\n";

        // Build fast lookup indexes on DB Assets
        $dbByCode = [];
        $dbByHull = [];
        $dbBySn = [];
        $dbByPlate = [];

        foreach ($this->dbAssets as $idx => $asset) {
            $codeKey = strtoupper(preg_replace('/[^A-Z0-9]/', '', $asset['asset_code']));
            if ($codeKey !== '') $dbByCode[$codeKey][] = $idx;

            $hullKey = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string)$asset['hull_number']));
            if ($hullKey !== '') $dbByHull[$hullKey][] = $idx;

            $snKey = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string)$asset['serial_number']));
            if ($snKey !== '') $dbBySn[$snKey][] = $idx;

            $plateKey = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string)$asset['license_plate']));
            if ($plateKey !== '') $dbByPlate[$plateKey][] = $idx;
        }

        $matchedDbIndices = [];

        foreach ($this->rekapRows as &$rekap) {
            $candidates = []; // assetIndex => score

            // 1. Check Serial Number match
            foreach ($rekap['tokens'] as $tok) {
                if ($tok['type'] === 'SN_OR_CHASSIS' && isset($dbBySn[$tok['compact']])) {
                    foreach ($dbBySn[$tok['compact']] as $idx) {
                        $candidates[$idx] = ($candidates[$idx] ?? 0) + 100;
                    }
                }
            }

            // 2. Check Plate match
            foreach ($rekap['tokens'] as $tok) {
                if ($tok['type'] === 'PLATE') {
                    if (isset($dbByPlate[$tok['compact']])) {
                        foreach ($dbByPlate[$tok['compact']] as $idx) {
                            $candidates[$idx] = ($candidates[$idx] ?? 0) + 95;
                        }
                    }
                    // Check if plate has mutation record
                    if (isset($this->mutasiLookup[$tok['clean']])) {
                        $mut = $this->mutasiLookup[$tok['clean']];
                        $newCompact = preg_replace('/\s+/', '', $mut['new_plate']);
                        if ($newCompact !== '' && isset($dbByPlate[$newCompact])) {
                            foreach ($dbByPlate[$newCompact] as $idx) {
                                $candidates[$idx] = ($candidates[$idx] ?? 0) + 90;
                            }
                        }
                    }
                }
            }

            // 3. Check Nomor Lambung Aktual vs asset_code / hull_number
            $lambungKey = strtoupper(preg_replace('/[^A-Z0-9]/', '', $rekap['lambung']));
            if ($lambungKey !== '') {
                if (isset($dbByCode[$lambungKey])) {
                    foreach ($dbByCode[$lambungKey] as $idx) {
                        $candidates[$idx] = ($candidates[$idx] ?? 0) + 85;
                    }
                }
                if (isset($dbByHull[$lambungKey])) {
                    foreach ($dbByHull[$lambungKey] as $idx) {
                        $candidates[$idx] = ($candidates[$idx] ?? 0) + 85;
                    }
                }
            }

            // 4. Check Kode Unit
            $kodeKey = strtoupper(preg_replace('/[^A-Z0-9]/', '', $rekap['kode_unit']));
            if ($kodeKey !== '') {
                if (isset($dbByCode[$kodeKey])) {
                    foreach ($dbByCode[$kodeKey] as $idx) {
                        $candidates[$idx] = ($candidates[$idx] ?? 0) + 80;
                    }
                }
                if (isset($dbByHull[$kodeKey])) {
                    foreach ($dbByHull[$kodeKey] as $idx) {
                        $candidates[$idx] = ($candidates[$idx] ?? 0) + 80;
                    }
                }
            }

            // Evaluation of candidates for this rekap row
            if (empty($candidates)) {
                $this->newCandidates[] = $rekap;
                continue;
            }

            arsort($candidates);
            $topScore = reset($candidates);
            $topIndex = key($candidates);

            // Check if top candidate is tied (Conflict)
            $topCandidates = array_keys(array_filter($candidates, fn($s) => $s === $topScore));
            if (count($topCandidates) > 1) {
                $this->conflicts[] = [
                    'type' => 'AMBIGUOUS_REKAP_ROW',
                    'rekap' => $rekap,
                    'candidate_assets' => array_map(fn($i) => $this->dbAssets[$i], $topCandidates),
                    'score' => $topScore
                ];
                continue;
            }

            $matchedAsset = $this->dbAssets[$topIndex];

            // Category cross-check for bonus confidence
            $rekapCat = strtoupper($rekap['kategori_clean']);
            $dbCat = strtoupper($matchedAsset['category']);
            if (str_contains($rekapCat, $dbCat) || str_contains($dbCat, $rekapCat)) {
                $topScore += 10;
            }

            $matchRecord = [
                'rekap' => $rekap,
                'asset' => $matchedAsset,
                'score' => $topScore,
                'proposed_diff' => $this->buildProposedDiff($matchedAsset, $rekap)
            ];

            if ($topScore >= 95) {
                $this->matchAuto[] = $matchRecord;
                $matchedDbIndices[$topIndex] = true;
                $rekap['matched_asset_id'] = $matchedAsset['asset_id'];
            } else {
                $this->matchReview[] = $matchRecord;
                $matchedDbIndices[$topIndex] = true;
                $rekap['matched_asset_id'] = $matchedAsset['asset_id'];
            }
        }

        // Identify DB Assets that had NO evidence matched
        foreach ($this->dbAssets as $idx => $asset) {
            if (!isset($matchedDbIndices[$idx])) {
                $this->noEvidence[] = $asset;
            }
        }
    }

    private function buildProposedDiff(array $dbAsset, array $rekap): array
    {
        $diffs = [];

        // 1. Serial Number
        $currentSn = trim((string)$dbAsset['serial_number']);
        $proposedSn = '';
        foreach ($rekap['tokens'] as $tok) {
            if ($tok['type'] === 'SN_OR_CHASSIS') {
                $proposedSn = $tok['clean'];
                break;
            }
        }
        if ($currentSn === '' && $proposedSn !== '') {
            $diffs['serial_number'] = [
                'current' => null,
                'proposed' => $proposedSn,
                'source' => 'REKAP 2026'
            ];
        }

        // 2. License Plate & Previous License Plate
        $currentPlate = trim((string)$dbAsset['license_plate']);
        $proposedPlate = '';
        $previousPlate = '';

        foreach ($rekap['tokens'] as $tok) {
            if ($tok['type'] === 'PLATE') {
                $plateClean = $tok['clean'];
                // Check if mutasi finished
                if (isset($this->mutasiLookup[$plateClean])) {
                    $mut = $this->mutasiLookup[$plateClean];
                    if ($mut['is_done'] && !empty($mut['new_plate'])) {
                        $proposedPlate = $mut['new_plate'];
                        $previousPlate = $plateClean;
                    } else {
                        $proposedPlate = $plateClean;
                    }
                } else {
                    $proposedPlate = $plateClean;
                }
                break;
            }
        }

        if ($currentPlate === '' && $proposedPlate !== '') {
            $diffs['license_plate'] = [
                'current' => null,
                'proposed' => $proposedPlate,
                'source' => $previousPlate !== '' ? 'Mutasi Duri (Close)' : 'REKAP 2026'
            ];
        }
        if ($previousPlate !== '' && trim((string)$dbAsset['previous_license_plate']) === '') {
            $diffs['previous_license_plate'] = [
                'current' => null,
                'proposed' => $previousPlate,
                'source' => 'Mutasi Duri Riwayat'
            ];
        }

        // 3. Hull Number
        $currentHull = trim((string)$dbAsset['hull_number']);
        $proposedHull = trim((string)$rekap['kode_unit']);
        if ($currentHull === '' && $proposedHull !== '') {
            $diffs['hull_number'] = [
                'current' => null,
                'proposed' => $proposedHull,
                'source' => 'REKAP 2026'
            ];
        }

        // 4. Dealer
        $currentDealer = trim((string)$dbAsset['dealer']);
        $proposedDealer = trim((string)$rekap['dealer']);
        if ($currentDealer === '' && $proposedDealer !== '') {
            $diffs['dealer'] = [
                'current' => null,
                'proposed' => $proposedDealer,
                'source' => 'REKAP 2026'
            ];
        }

        // 5. Capacity
        $currentCap = trim((string)$dbAsset['capacity']);
        $proposedCap = trim((string)$rekap['kapasitas']);
        if ($currentCap === '' && $proposedCap !== '') {
            $diffs['capacity'] = [
                'current' => null,
                'proposed' => $proposedCap,
                'source' => 'REKAP 2026'
            ];
        }

        // 6. Project Name
        $currentProject = trim((string)$dbAsset['project_name']);
        $proposedProject = trim((string)$rekap['project']);
        if ($currentProject === '' && $proposedProject !== '') {
            $diffs['project_name'] = [
                'current' => null,
                'proposed' => $proposedProject,
                'source' => 'REKAP 2026'
            ];
        }

        // 7. Year Manufacture
        $currentYear = (int)($dbAsset['year_manufacture'] ?? 0);
        $proposedYear = (int)$rekap['tahun'];
        if ($currentYear === 0 && $proposedYear >= 1970 && $proposedYear <= 2099) {
            $diffs['year_manufacture'] = [
                'current' => null,
                'proposed' => $proposedYear,
                'source' => 'REKAP 2026'
            ];
        }

        return $diffs;
    }

    private function printConsoleSummary(): void
    {
        echo "\n[4/4] Resolution & Triase Results Summary:\n";
        echo "--------------------------------------------------------------------\n";
        echo " 1. MATCH_AUTO (Score >= 95, Clean Unique Match):  " . count($this->matchAuto) . " units\n";
        echo " 2. MATCH_REVIEW (Score 80-94, Candidate Match):   " . count($this->matchReview) . " units\n";
        echo " 3. CONFLICTS (Tied Ambiguities / Cross-Claims):   " . count($this->conflicts) . " cases\n";
        echo " 4. NEW_CANDIDATE (REKAP rows without DB match):   " . count($this->newCandidates) . " rows\n";
        echo " 5. NO_EVIDENCE (DB Assets without REKAP match):   " . count($this->noEvidence) . " units\n";
        echo "--------------------------------------------------------------------\n";
        echo " Total DB Units Monitored: " . count($this->dbAssets) . "\n";
        echo " Total REKAP Rows Parsed:  " . count($this->rekapRows) . "\n\n";
    }

    private function generateMarkdownReport(): void
    {
        $dir = __DIR__ . '/../reports';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $reportPath = $dir . '/REGISTER_NORMALISASI_MASTER_ASSET.md';
        $fp = fopen($reportPath, 'w');

        fwrite($fp, "# Register Normalisasi Master Asset: Matriks Audit & Staging Triase\n\n");
        fwrite($fp, "**Tanggal Ekstraksi:** 23 Oktober 2026  \n");
        fwrite($fp, "**Mode:** Read-Only Audit & Preview Diff (Tidak ada update database)  \n");
        fwrite($fp, "**Basis Data:** `u646470441_ServicePlanBRA` (283 Unit Aktif)  \n");
        fwrite($fp, "**Sumber Verifikasi:** `REKAP 2026` (332 Baris), `Mutasi Unit Duri`, dan `KOMTRAX 2026`  \n\n");

        fwrite($fp, "## 1. Ringkasan Statistik Triase\n\n");
        fwrite($fp, "| Kategori Triase | Jumlah | Definisi Tindakan |\n");
        fwrite($fp, "|---|---:|---|\n");
        fwrite($fp, "| `MATCH_AUTO` | " . count($this->matchAuto) . " | Skor >= 95. Identitas unik terkonfirmasi tanpa kontradiksi. Siap diajukan ke pilot staging. |\n");
        fwrite($fp, "| `MATCH_REVIEW` | " . count($this->matchReview) . " | Skor 80-94. Indikasi kuat namun membutuhkan konfirmasi verifikator/PIC. |\n");
        fwrite($fp, "| `CONFLICT` | " . count($this->conflicts) . " | Baris bertentangan atau identitas diklaim > 1 unit. Pembaruan wajib ditahan. |\n");
        fwrite($fp, "| `NEW_CANDIDATE` | " . count($this->newCandidates) . " | Baris REKAP tidak cocok ke 283 unit aktif (kandidat unit baru, rental, atau nonaktif). |\n");
        fwrite($fp, "| `NO_EVIDENCE` | " . count($this->noEvidence) . " | Unit aktif database belum ditemukan di sumber REKAP 2026. Perlu cek fisik/lapangan. |\n\n");

        // Section 2: Rekomendasi 10 Unit Pilot
        fwrite($fp, "## 2. Rekomendasi 10 Unit Pilot (Sesuai Rencana Strategis Section 9 & 16)\n\n");
        fwrite($fp, "10 Unit berikut dipilih sebagai sampel representatif untuk uji coba dry-run tahap pertama:\n\n");
        fwrite($fp, "| No | Asset ID | Kode Unit | Kategori | Model | Usulan Pengisian Kunci | Sumber Bukti |\n");
        fwrite($fp, "|---:|---|---|---|---|---|---|\n");

        $pilotSample = array_slice($this->matchAuto, 0, 10);
        $pNo = 1;
        foreach ($pilotSample as $item) {
            $a = $item['asset'];
            $diffs = $item['proposed_diff'];
            $diffDesc = [];
            foreach ($diffs as $field => $d) {
                $diffDesc[] = "<b>$field:</b> " . htmlspecialchars((string)$d['proposed']);
            }
            $diffText = !empty($diffDesc) ? implode('<br>', $diffDesc) : '<i>Identitas sudah cocok</i>';
            fwrite($fp, sprintf(
                "| %d | `%s` | `%s` | %s | %s | %s | REKAP 2026 (Skor: %d) |\n",
                $pNo++,
                $a['asset_id'],
                $a['asset_code'],
                $a['category'],
                $a['make_model'],
                $diffText,
                $item['score']
            ));
        }

        // Section 3: MATCH_AUTO Register (Top 25)
        fwrite($fp, "\n## 3. Sampel Register MATCH_AUTO (Skor Tinggi, Siap Pilot)\n\n");
        fwrite($fp, "| Asset ID | Kode DB | Lambung REKAP | Model REKAP | SN/Pelat Sumber | Usulan Perubahan | Skor |\n");
        fwrite($fp, "|---|---|---|---|---|---|---:|\n");

        foreach (array_slice($this->matchAuto, 0, 25) as $item) {
            $a = $item['asset'];
            $r = $item['rekap'];
            $diffs = $item['proposed_diff'];
            $diffSummary = [];
            foreach ($diffs as $f => $d) {
                $diffSummary[] = "$f: " . $d['proposed'];
            }
            fwrite($fp, sprintf(
                "| `%s` | %s | %s | %s | %s | %s | %d |\n",
                $a['asset_id'],
                $a['asset_code'],
                $r['lambung'],
                $r['nama_asset'],
                $r['raw_sn_plate'],
                !empty($diffSummary) ? implode('; ', $diffSummary) : '<i>Lengkap</i>',
                $item['score']
            ));
        }

        // Section 4: CONFLICT & Ambiguities
        fwrite($fp, "\n## 4. Antrean Kasus Konflik & Ambigu (Wajib Verifikasi Manusia)\n\n");
        if (empty($this->conflicts)) {
            fwrite($fp, "Tidak ada konflik identitas majemuk ganda pada putaran pertama ekstraksi.\n\n");
        } else {
            fwrite($fp, "| Baris REKAP | Nama Asset | Identitas Sumber | Unit DB Terkait | Catatan Evaluator |\n");
            fwrite($fp, "|---|---|---|---|---|\n");
            foreach ($this->conflicts as $c) {
                $r = $c['rekap'];
                $dbUnits = array_map(fn($a) => $a['asset_code'] . " ({$a['asset_id']})", $c['candidate_assets']);
                fwrite($fp, sprintf(
                    "| Baris %s | %s | %s | %s | Terdeteksi skor sama pada lebih dari satu unit DB. Ditahan. |\n",
                    $r['row_num'],
                    $r['nama_asset'],
                    $r['raw_sn_plate'],
                    implode(', ', $dbUnits)
                ));
            }
        }

        // Section 5: NEW_CANDIDATE Samples
        fwrite($fp, "\n## 5. Sampel NEW_CANDIDATE dari REKAP 2026 (Tidak Ada di 283 Unit DB)\n\n");
        fwrite($fp, "| Baris | Nama Asset | Kode Unit Sumber | Lambung | SN / Pelat | Kategori | Lokasi |\n");
        fwrite($fp, "|---|---|---|---|---|---|---|\n");
        foreach (array_slice($this->newCandidates, 0, 15) as $nc) {
            fwrite($fp, sprintf(
                "| %s | %s | %s | %s | %s | %s | %s |\n",
                $nc['row_num'],
                $nc['nama_asset'],
                $nc['kode_unit'],
                $nc['lambung'],
                $nc['raw_sn_plate'],
                $nc['kategori_clean'],
                $nc['lokasi']
            ));
        }

        fclose($fp);
        echo "      [SUCCESS] Generated Markdown report: $reportPath\n";
    }

    private function generateJsonExport(): void
    {
        $jsonPath = __DIR__ . '/../reports/normalization_pilot_candidates.json';
        $exportData = [
            'metadata' => [
                'extracted_at' => date('Y-m-d H:i:s'),
                'total_db_assets' => count($this->dbAssets),
                'total_rekap_rows' => count($this->rekapRows),
                'match_auto_count' => count($this->matchAuto),
                'match_review_count' => count($this->matchReview),
                'conflict_count' => count($this->conflicts),
                'new_candidates_count' => count($this->newCandidates),
                'no_evidence_count' => count($this->noEvidence),
            ],
            'pilot_10_units' => array_slice($this->matchAuto, 0, 10),
            'conflicts' => $this->conflicts,
        ];

        file_put_contents($jsonPath, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "      [SUCCESS] Exported JSON data: $jsonPath\n\n";
    }
}

// Execute
$matcher = new AssetNormalizationMatcher();
$matcher->run();
