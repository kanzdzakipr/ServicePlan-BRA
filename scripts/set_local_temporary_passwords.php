<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Script ini hanya dapat dijalankan melalui PHP CLI.\n");
    exit(1);
}

$environment = strtolower(trim((string) (getenv('APP_ENV') ?: '')));
if (!in_array($environment, ['local', 'development', 'test'], true)) {
    fwrite(STDERR, "DITOLAK: set APP_ENV=local, development, atau test. Script tidak boleh dijalankan di production.\n");
    exit(1);
}

if (!in_array('--confirm-local-reset', $argv, true)) {
    fwrite(STDERR, "Tambahkan --confirm-local-reset untuk mengonfirmasi reset seluruh password user aktif lokal.\n");
    exit(1);
}

$host = trim((string) (getenv('DB_HOST') ?: '127.0.0.1'));
$port = trim((string) (getenv('DB_PORT') ?: '3306'));
$database = trim((string) (getenv('DB_NAME') ?: 'serviceplan_bra'));
$username = trim((string) (getenv('DB_USER') ?: 'root'));
$passwordValue = getenv('DB_PASSWORD');
if ($passwordValue === false) {
    $passwordValue = getenv('DB_PASS');
}
$databasePassword = $passwordValue !== false ? (string) $passwordValue : '';

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $username,
        $databasePassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $users = $pdo->query(
        "SELECT user_id, username
         FROM users
         WHERE is_active = 1
         ORDER BY user_id"
    )->fetchAll();

    if ($users === []) {
        throw new RuntimeException('Tidak ada user aktif pada database lokal.');
    }

    $update = $pdo->prepare(
        'UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id'
    );

    $pdo->beginTransaction();
    foreach ($users as $user) {
        $temporaryPassword = (string) $user['username'] . '123';
        $update->execute([
            ':password_hash' => password_hash($temporaryPassword, PASSWORD_DEFAULT),
            ':user_id' => (int) $user['user_id'],
        ]);
    }
    $pdo->commit();

    fwrite(STDOUT, "Password sementara lokal berhasil dibuat:\n\n");
    foreach ($users as $user) {
        fwrite(STDOUT, $user['username'] . ' => ' . $user['username'] . "123\n");
    }
    fwrite(STDOUT, "\nPola password ini ditolak oleh login production dan wajib diganti sebelum deployment.\n");
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Reset password lokal gagal: " . $error->getMessage() . "\n");
    exit(1);
}
