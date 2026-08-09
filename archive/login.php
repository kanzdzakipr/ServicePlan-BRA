<?php
require __DIR__ . '/app/bootstrap.php';
if (is_logged_in()) redirect('dashboard.php');
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    $usesCompromisedSeedHash = $user && hash_equals(
        'f86e9cbe593c35b0ae64e8e091c6f3002a8081c2e92de7747fd44a71959b27bd',
        hash('sha256', (string)$user['password_hash'])
    );
    if ($user && !$usesCompromisedSeedHash && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id'=>$user['id'],'name'=>$user['name'],'email'=>$user['email'],'role'=>$user['role']];
        $_SESSION['created_at'] = time();
        $_SESSION['last_activity'] = time();
        audit($pdo,'LOGIN','user',(int)$user['id'],'Login berhasil');
        redirect('dashboard.php');
    }
    $error = 'Email atau password tidak sesuai.';
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login AssetPro</title><link rel="stylesheet" href="assets/css/app.css"></head>
<body class="login-page"><div class="login-card"><div class="brand-login"><div class="brand-badge">A</div><div><h1>AssetPro</h1><p>Asset & Equipment Management</p></div></div>
<?php if($error): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif; ?>
<form method="post"><input type="hidden" name="_csrf" value="<?=csrf_token()?>">
<label>Email</label><input name="email" type="email" autocomplete="username" required>
<label>Password</label><input name="password" type="password" autocomplete="current-password" required>
<button class="btn primary full">Masuk</button></form>
</div></body></html>
