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
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user'] = ['id'=>$user['id'],'name'=>$user['name'],'email'=>$user['email'],'role'=>$user['role']];
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
<label>Email</label><input name="email" type="email" value="admin@assetpro.local" required>
<label>Password</label><input name="password" type="password" value="admin123" required>
<button class="btn primary full">Masuk</button></form>
<p class="login-hint">Akun awal: admin@assetpro.local / admin123</p></div></body></html>
