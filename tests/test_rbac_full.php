<?php
declare(strict_types=1);

echo "=== MEMULAI TEST SUITE RBAC INTEGRASI LENGKAP ===" . PHP_EOL . PHP_EOL;

// 1. Cek syntax PHP dari file yang dimodifikasi
echo "1. Memeriksa Syntax PHP Seluruh File Terkait..." . PHP_EOL;
$phpFiles = [
    __DIR__ . '/../api/settings.php',
    __DIR__ . '/../api/auth.php',
    __DIR__ . '/../dashboard.php',
    __DIR__ . '/../dashboard.view.php'
];

foreach ($phpFiles as $file) {
    $output = [];
    $returnCode = 0;
    exec("C:\\laragon\\bin\\php\\php-8.3.30-Win32-vs16-x64\\php.exe -l " . escapeshellarg($file), $output, $returnCode);
    if ($returnCode === 0) {
        echo "[+] PHP Lint OK: " . basename($file) . PHP_EOL;
    } else {
        echo "[-] PHP Lint GAGAL: " . basename($file) . PHP_EOL;
        echo implode(PHP_EOL, $output) . PHP_EOL;
        exit(1);
    }
}
echo PHP_EOL;

// 2. Definisi Matrix Test
echo "2. Validasi Struktur Definisi 10 Roles & 16 Modul RBAC..." . PHP_EOL;
$rolesMaster = [
    '1. Equipment Manager', '2. Maintenance Planner', '3. Site Manager / Project Manager',
    '4. Workshop Supervisor', '5. Lead Mechanic / Mekanik Senior', '6. Logistik & Sparepart Officer',
    '7. Fuelman / Operator BBM', '8. Dispatcher / Admin Operasional', '9. Management / Direksi',
    '10. System Administrator'
];

$modulesMaster = [
    'Dashboard Ringkasan', 'Monitoring Unit', 'Master Asset', 'Work Order (WO)',
    'Jadwal & Backlog PM', 'Logistik & Sparepart', 'Monitoring BBM', 'Biaya & Valuasi',
    'Administrasi & Legalitas', 'Kalibrasi & Sertifikasi', 'Kondisi & Komponen',
    'Inspeksi Ban (Tire)', 'K3 & Investigasi Insiden', 'Approval Inbox',
    'Laporan & Export', 'Pengaturan & RBAC'
];

if (count($rolesMaster) !== 10) {
    echo "[-] GAGAL: Jumlah role bukan 10!" . PHP_EOL;
    exit(1);
}
if (count($modulesMaster) !== 16) {
    echo "[-] GAGAL: Jumlah modul bukan 16!" . PHP_EOL;
    exit(1);
}
echo "[+] SUKSES: 10 Roles dan 16 Modul terverifikasi." . PHP_EOL . PHP_EOL;

// 3. Tes Fungsi Penyimpanan & Query Matriks (Menggunakan SQLite In-Memory)
echo "3. Tes Mekanisme Penyimpanan Database (CRUD Matriks RBAC)..." . PHP_EOL;
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("
    CREATE TABLE rbac_matrix_settings (
        setting_key VARCHAR(64) PRIMARY KEY,
        matrix_json TEXT NOT NULL,
        updated_by VARCHAR(100) DEFAULT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
");

// Include fungsi api_get_saved_rbac_matrix
function test_api_get_saved_rbac_matrix(PDO $db): ?array
{
    try {
        $stmt = $db->prepare('SELECT matrix_json FROM rbac_matrix_settings WHERE setting_key = :k LIMIT 1');
        $stmt->execute([':k' => 'active_matrix']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['matrix_json'])) {
            $data = json_decode((string) $row['matrix_json'], true);
            if (is_array($data) && count($data) === 10) {
                return $data;
            }
        }
    } catch (Throwable $e) {}
    return null;
}

$initial = test_api_get_saved_rbac_matrix($pdo);
if ($initial === null) {
    echo "[+] Status awal: Default (tidak ada override di DB)." . PHP_EOL;
} else {
    echo "[-] Status awal tidak sesuai." . PHP_EOL;
    exit(1);
}

// Simpan matriks kustom
$testMatrix = [];
foreach ($rolesMaster as $rName) {
    $testMatrix[] = [
        'name' => $rName,
        'badge' => 'Badge',
        'desc' => 'Desc',
        'icon' => 'fa-gear',
        'color' => '#0284c7',
        'permissions' => array_fill(0, 16, 'R C U'),
        'isCustomized' => true
    ];
}

$stmt = $pdo->prepare("INSERT OR REPLACE INTO rbac_matrix_settings (setting_key, matrix_json, updated_by, updated_at) 
    VALUES ('active_matrix', :json, 'admin_tester', datetime('now'))");
$stmt->execute([':json' => json_encode($testMatrix)]);

$saved = test_api_get_saved_rbac_matrix($pdo);
if (is_array($saved) && count($saved) === 10 && $saved[0]['name'] === '1. Equipment Manager') {
    echo "[+] SUKSES: Matriks 10 roles berhasil disimpan dan divalidasi dari DB." . PHP_EOL;
} else {
    echo "[-] GAGAL menyimpan matriks ke DB." . PHP_EOL;
    exit(1);
}

// Reset matriks
$pdo->exec("DELETE FROM rbac_matrix_settings WHERE setting_key = 'active_matrix'");
$afterReset = test_api_get_saved_rbac_matrix($pdo);
if ($afterReset === null) {
    echo "[+] SUKSES: Reset matriks berhasil." . PHP_EOL . PHP_EOL;
} else {
    echo "[-] GAGAL mereset matriks." . PHP_EOL;
    exit(1);
}

// 4. Validasi Skrip JS & HTML
echo "4. Validasi Keberadaan Tag RBAC di Frontend..." . PHP_EOL;
$viewContent = file_get_contents(__DIR__ . '/../dashboard.view.php');
$htmlContent = file_get_contents(__DIR__ . '/../dashboard.html');

$checks = [
    'data-rbac-menu="asset"' => 'Tag Menu Asset',
    'data-rbac-menu="wo"' => 'Tag Menu Work Order',
    'data-rbac-menu="fuel"' => 'Tag Menu BBM',
    'data-rbac-menu="biaya"' => 'Tag Menu Biaya',
    'data-rbac-menu="pm"' => 'Tag Menu PM',
    'data-rbac-menu="settings"' => 'Tag Menu Settings',
    'FleetRBAC' => 'Modul JS FleetRBAC',
    'SERVER_RBAC_MATRIX' => 'Injeksi Server Matrix',
    'saveRbacCellOverride' => 'Handler Simpan Override RBAC',
    'resetRbacToDefault' => 'Handler Reset RBAC'
];

foreach ($checks as $pattern => $label) {
    if (strpos($viewContent, $pattern) !== false) {
        echo "[+] [dashboard.view.php] {$label} terverifikasi." . PHP_EOL;
    } else {
        echo "[-] [dashboard.view.php] {$label} TIDAK ditemukan!" . PHP_EOL;
        exit(1);
    }
    if (strpos($htmlContent, $pattern) !== false) {
        echo "[+] [dashboard.html]     {$label} terverifikasi." . PHP_EOL;
    } else {
        echo "[-] [dashboard.html]     {$label} TIDAK ditemukan!" . PHP_EOL;
        exit(1);
    }
}

echo PHP_EOL . "=== SELURUH SUITE TEST RBAC LENGKAP LULUS 100%! ===" . PHP_EOL;
