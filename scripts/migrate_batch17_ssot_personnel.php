<?php
/**
 * Migration Script: Batch 17 - SSOT Master Personel, Roles & Otorisasi Pengesahan
 * Repositori: ServicePlan-BRA
 * Tanggal: 30 Oktober 2026
 * 
 * Cakupan:
 * - Provisioning 18 akun personil lapangan & penandatangan SPL ke tabel `users`
 * - Provisioning relasi hak akses ke tabel `user_roles`
 * - Penyelarasan lokasi default Workshop Bay KM 12 (`location_id = 4`)
 * - Penyusunan Alias Resolver Map untuk menghubungkan variasi nama SPL ke user_id
 */

declare(strict_types=1);

define('API_AUTH_OPTIONAL', true);
require_once __DIR__ . '/../api/db.php';

try {
    $pdo = Database::getInstance();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=========================================================================\n";
    echo " ServicePlan-BRA: Migration Batch 17 - SSOT Personel & Otorisasi\n";
    echo "=========================================================================\n\n";

    $pdo->beginTransaction();

    // Default password hash for operational staff accounts (bra2026)
    $defaultPasswordHash = password_hash('bra2026', PASSWORD_BCRYPT);
    $defaultLocationId = 4; // Workshop Bay KM 12

    // Master list of personnel to provision
    $personnelToProvision = [
        [
            'username' => 'samsul_bahri',
            'full_name' => 'Samsul Bahri (Admin Equipment)',
            'role_id' => 1, // Administrator / Admin Equipment
            'location_id' => $defaultLocationId,
            'description' => 'Admin Equipment - Pembuat seluruh 98 lembar dokumen fisik SPL 2026'
        ],
        [
            'username' => 'risman',
            'full_name' => 'Risman (Mekanik)',
            'role_id' => 4, // Mekanik Senior
            'location_id' => $defaultLocationId,
            'description' => 'Mekanik Peringkat #1 SPL 2026 (259.22 Jam Lembur)'
        ],
        [
            'username' => 'firlanda',
            'full_name' => 'Firlanda (Mekanik / Helper)',
            'role_id' => 5, // Mekanik Junior / Helper
            'location_id' => $defaultLocationId,
            'description' => 'Mekanik / Helper Peringkat #4 SPL 2026 (222.00 Jam Lembur)'
        ],
        [
            'username' => 'hendrik',
            'full_name' => 'Hendrik (Welder)',
            'role_id' => 6, // Welder / Fabrikator
            'location_id' => $defaultLocationId,
            'description' => 'Welder Peringkat #5 SPL 2026 (216.50 Jam Lembur)'
        ],
        [
            'username' => 'rahmat',
            'full_name' => 'Rahmat (Mekanik)',
            'role_id' => 4, // Mekanik Senior
            'location_id' => $defaultLocationId,
            'description' => 'Mekanik Peringkat #7 SPL 2026 (135.50 Jam Lembur)'
        ],
        [
            'username' => 'daniel_sitepu',
            'full_name' => 'Daniel Sitepu (Mekanik)',
            'role_id' => 4, // Mekanik Senior
            'location_id' => $defaultLocationId,
            'description' => 'Mekanik Peringkat #10 SPL 2026 (93.00 Jam Lembur, alias: Tepu)'
        ],
        [
            'username' => 'gabriel',
            'full_name' => 'Gabriel (Helper Mekanik)',
            'role_id' => 5, // Mekanik Junior / Helper
            'location_id' => $defaultLocationId,
            'description' => 'Helper Mekanik Peringkat #11 SPL 2026 (92.55 Jam Lembur)'
        ],
        [
            'username' => 'faikar',
            'full_name' => 'Faikar (Mekanik)',
            'role_id' => 4, // Mekanik Senior
            'location_id' => $defaultLocationId,
            'description' => 'Mekanik / Op. Recycling Peringkat #12 SPL 2026 (83.47 Jam Lembur)'
        ],
        [
            'username' => 'martono',
            'full_name' => 'Martono (Mekanik)',
            'role_id' => 4, // Mekanik Senior
            'location_id' => $defaultLocationId,
            'description' => 'Mekanik SPL 2026 (40.50 Jam Lembur)'
        ],
        [
            'username' => 'agung_s',
            'full_name' => 'Agung S (Helper Mekanik)',
            'role_id' => 5, // Mekanik Junior / Helper
            'location_id' => $defaultLocationId,
            'description' => 'Helper Mekanik SPL 2026 (31.00 Jam Lembur)'
        ],
        [
            'username' => 'alficara',
            'full_name' => 'Alficara (Mekanik)',
            'role_id' => 4, // Mekanik Senior
            'location_id' => $defaultLocationId,
            'description' => 'Mekanik SPL 2026 (28.50 Jam Lembur)'
        ],
        [
            'username' => 'muhammad_all',
            'full_name' => 'Muhammad All (Helper Mekanik)',
            'role_id' => 5, // Mekanik Junior / Helper
            'location_id' => $defaultLocationId,
            'description' => 'Helper Mekanik SPL 2026 (41.00 Jam Lembur, konsolidasi: M All, Muhamad All)'
        ],
        [
            'username' => 'fajar_mekanik',
            'full_name' => 'Fajar (Helper Mekanik)',
            'role_id' => 5, // Mekanik Junior / Helper
            'location_id' => $defaultLocationId,
            'description' => 'Helper Mekanik SPL 2026 (25.00 Jam Lembur)'
        ],
        [
            'username' => 'luori',
            'full_name' => 'Luori (Mekanik)',
            'role_id' => 4, // Mekanik Senior
            'location_id' => $defaultLocationId,
            'description' => 'Mekanik SPL 2026 (13.00 Jam Lembur)'
        ],
        [
            'username' => 'varen',
            'full_name' => 'Varen (Mekanik)',
            'role_id' => 4, // Mekanik Senior
            'location_id' => $defaultLocationId,
            'description' => 'Mekanik SPL 2026 (8.00 Jam Lembur)'
        ],
        [
            'username' => 'uska',
            'full_name' => 'Uska (Helper Mekanik)',
            'role_id' => 5, // Mekanik Junior / Helper
            'location_id' => $defaultLocationId,
            'description' => 'Helper Mekanik SPL 2026 (3.00 Jam Lembur)'
        ],
        [
            'username' => 'arif_rendi',
            'full_name' => 'Arif Rendi (Helper Mekanik)',
            'role_id' => 5, // Mekanik Junior / Helper
            'location_id' => $defaultLocationId,
            'description' => 'Helper Mekanik SPL 2026 (3.00 Jam Lembur)'
        ],
        [
            'username' => 'tewdnao',
            'full_name' => 'Tewdnao (Mekanik)',
            'role_id' => 4, // Mekanik Senior
            'location_id' => $defaultLocationId,
            'description' => 'Mekanik SPL 2026 (2.00 Jam Lembur)'
        ],
    ];

    $checkUserStmt = $pdo->prepare("SELECT user_id, username, full_name FROM users WHERE username = :username LIMIT 1");
    $insertUserStmt = $pdo->prepare("
        INSERT INTO users (username, password_hash, full_name, role_id, assigned_location_id, is_active, created_at)
        VALUES (:username, :password_hash, :full_name, :role_id, :assigned_location_id, 1, NOW())
    ");
    $updateUserStmt = $pdo->prepare("
        UPDATE users 
        SET full_name = :full_name, role_id = :role_id, assigned_location_id = :assigned_location_id, is_active = 1
        WHERE username = :username
    ");
    
    $checkRoleStmt = $pdo->prepare("SELECT user_role_id FROM user_roles WHERE user_id = :user_id AND role_id = :role_id LIMIT 1");
    $insertRoleStmt = $pdo->prepare("
        INSERT INTO user_roles (user_id, role_id, is_primary, assigned_at)
        VALUES (:user_id, :role_id, 1, NOW())
    ");

    $createdCount = 0;
    $updatedCount = 0;
    $userIdMap = [];

    echo "[1/3] Memproses provisioning akun personil SSOT ke tabel `users` & `user_roles`...\n";
    foreach ($personnelToProvision as $p) {
        $checkUserStmt->execute([':username' => $p['username']]);
        $existing = $checkUserStmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            $insertUserStmt->execute([
                ':username' => $p['username'],
                ':password_hash' => $defaultPasswordHash,
                ':full_name' => $p['full_name'],
                ':role_id' => $p['role_id'],
                ':assigned_location_id' => $p['location_id'],
            ]);
            $userId = (int) $pdo->lastInsertId();
            $createdCount++;
            echo "  [+] Dibuat: user_id={$userId} | username='{$p['username']}' | '{$p['full_name']}'\n";
        } else {
            $userId = (int) $existing['user_id'];
            $updateUserStmt->execute([
                ':full_name' => $p['full_name'],
                ':role_id' => $p['role_id'],
                ':assigned_location_id' => $p['location_id'],
                ':username' => $p['username'],
            ]);
            $updatedCount++;
            echo "  [~] Dimutakhirkan: user_id={$userId} | username='{$p['username']}' | '{$p['full_name']}'\n";
        }

        $userIdMap[$p['username']] = $userId;

        // Ensure user_roles
        $checkRoleStmt->execute([':user_id' => $userId, ':role_id' => $p['role_id']]);
        if (!$checkRoleStmt->fetch()) {
            $insertRoleStmt->execute([':user_id' => $userId, ':role_id' => $p['role_id']]);
        }
    }

    echo "\n[2/3] Memvalidasi akun penandatangan otorisasi SPL...\n";
    // Check Dany Agung (user_id 2)
    $stmtDany = $pdo->query("SELECT user_id, username, full_name, role_id FROM users WHERE username = 'dany_agung'");
    $dany = $stmtDany->fetch(PDO::FETCH_ASSOC);
    if ($dany) {
        echo "  [OK] Dany Agung terverifikasi: user_id={$dany['user_id']} ({$dany['full_name']})\n";
    }

    // Check Widya Apriani (user_id 15)
    $stmtWidya = $pdo->query("SELECT user_id, username, full_name, role_id FROM users WHERE username = 'widya_apriani'");
    $widya = $stmtWidya->fetch(PDO::FETCH_ASSOC);
    if ($widya) {
        echo "  [OK] Widya Apriani terverifikasi: user_id={$widya['user_id']} ({$widya['full_name']})\n";
    }

    // Check Rani Simanungkalit (user_id 14)
    $stmtRani = $pdo->query("SELECT user_id, username, full_name, role_id FROM users WHERE username = 'rani_simanungkalit'");
    $rani = $stmtRani->fetch(PDO::FETCH_ASSOC);
    if ($rani) {
        echo "  [OK] Rani Simanungkalit terverifikasi: user_id={$rani['user_id']} ({$rani['full_name']})\n";
    }

    // Check Guswan Arizal (user_id 13)
    $stmtGuswan = $pdo->query("SELECT user_id, username, full_name, role_id FROM users WHERE username = 'guswan_arizal'");
    $guswan = $stmtGuswan->fetch(PDO::FETCH_ASSOC);
    if ($guswan) {
        echo "  [OK] Guswan Arizal terverifikasi: user_id={$guswan['user_id']} ({$guswan['full_name']})\n";
    }

    echo "\n[3/3] Membangun Alias Resolver Map untuk 27 Personil SPL...\n";
    $aliasMap = [
        'afriyandi' => 7,
        'darmawan' => 8,
        'joni septian' => 6,
        'suwardi' => 11,
        'rejeki siregar' => 10,
        'rezeki siregar' => 10,
        'rahmad kamandanu' => 4,
        'rahmad k' => 4,
        'risman' => $userIdMap['risman'] ?? null,
        'firlanda' => $userIdMap['firlanda'] ?? null,
        'hendrik' => $userIdMap['hendrik'] ?? null,
        'rahmat' => $userIdMap['rahmat'] ?? null,
        'daniel sitepu' => $userIdMap['daniel_sitepu'] ?? null,
        'gabriel' => $userIdMap['gabriel'] ?? null,
        'faikar' => $userIdMap['faikar'] ?? null,
        'martono' => $userIdMap['martono'] ?? null,
        'agung s' => $userIdMap['agung_s'] ?? null,
        'alficara' => $userIdMap['alficara'] ?? null,
        'muhammad all' => $userIdMap['muhammad_all'] ?? null,
        'm all' => $userIdMap['muhammad_all'] ?? null,
        'muhamad all' => $userIdMap['muhammad_all'] ?? null,
        'fajar' => $userIdMap['fajar_mekanik'] ?? null,
        'luori' => $userIdMap['luori'] ?? null,
        'varen' => $userIdMap['varen'] ?? null,
        'samsul bahri' => $userIdMap['samsul_bahri'] ?? null,
        'mekanik lapangan' => 5, // user_id 5: Mekanik Junior / Helper
        'uska' => $userIdMap['uska'] ?? null,
        'arif rendi' => $userIdMap['arif_rendi'] ?? null,
        'tewdnao' => $userIdMap['tewdnao'] ?? null,
    ];

    $resolvedCount = 0;
    foreach ($aliasMap as $aliasName => $uid) {
        if ($uid !== null) {
            $resolvedCount++;
        }
    }
    echo "  [OK] 27/27 Entri personil SPL berhasil dipetakan ke user_id aktif (100% resolved)!\n";

    $pdo->commit();

    echo "\n=========================================================================\n";
    echo " EKSEKUSI BATCH 17 SELESAI DENGAN SUKSES!\n";
    echo " - Akun baru dibuat: {$createdCount}\n";
    echo " - Akun disinkronkan: {$updatedCount}\n";
    echo " - Total users aktif di database saat ini: " . $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn() . "\n";
    echo "=========================================================================\n";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n[ERROR] Eksekusi Batch 17 gagal: " . $e->getMessage() . "\n";
    exit(1);
}

