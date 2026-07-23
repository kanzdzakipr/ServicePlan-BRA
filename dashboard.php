<?php
require __DIR__ . '/app/bootstrap.php';
require_login();
$page = $_GET['page'] ?? 'home';
$allowed = ['home','units','unit-form','maintenance','logistics','tires','grease','mechanics','condition','users','documents','approvals','history','notifications','reports','location-dashboard','analytics','qr','backup','audit'];
if (!in_array($page,$allowed,true)) $page='home';
$pagePermissions=['home'=>'dashboard','units'=>'units.view','unit-form'=>'units.edit','maintenance'=>'service.view','logistics'=>'logistics.view','tires'=>'inspections.view','grease'=>'inspections.view','mechanics'=>'inspections.view','condition'=>'inspections.view','users'=>'users.manage','documents'=>'documents.view','approvals'=>'logistics.approve','history'=>'history.view','notifications'=>'notifications.view','reports'=>'reports.export','location-dashboard'=>'analytics.view','analytics'=>'analytics.view','qr'=>'qr.view','backup'=>'backup.manage','audit'=>'audit.view'];
require_permission($pagePermissions[$page]??'dashboard');
require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
$stmt=$pdo->prepare('SELECT COUNT(*) FROM notifications WHERE is_read=0 AND (user_id IS NULL OR user_id=?)');$stmt->execute([current_user()['id']]);$unread=(int)$stmt->fetchColumn();
?>
<section class="content"><header class="topbar"><div><h2><?=e(ucwords(str_replace('-',' ',$page==='home'?'Dashboard Asset Manager':$page)))?></h2><p>Data operasional aset dan equipment terintegrasi.</p></div><div class="top-actions"><a class="notification-link" href="<?=page_url('notifications')?>">🔔 <?=$unread?></a><span class="user-name"><?=e(current_user()['name'])?> · <?=e(role_label(current_role()))?></span><a class="btn" href="logout.php">Keluar</a></div></header>
<main class="main">
<?php if($m=flash('success')): ?><div class="alert alert-success"><?=e($m)?></div><?php endif; ?>
<?php if($m=flash('error')): ?><div class="alert alert-danger"><?=e($m)?></div><?php endif; ?>
<?php require __DIR__ . '/pages/' . $page . '.php'; ?>
</main></section>
<?php require __DIR__ . '/partials/footer.php'; ?>
