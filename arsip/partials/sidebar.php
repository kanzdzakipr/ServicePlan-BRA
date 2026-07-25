<?php $current=$page; ?>
<aside class="sidebar"><div class="brand"><div class="brand-badge">A</div><div><h1>AssetPro</h1><span>Asset Management</span></div></div>
<a class="menu-item <?=active_page('home',$current)?>" href="<?=page_url('home')?>">⌂ Dashboard</a>
<div class="menu-title">Manajemen Aset</div>
<?php if(can('units.view')): ?><a class="menu-item <?=active_page('units',$current)?>" href="<?=page_url('units')?>">▣ Daftar Unit</a><?php endif; ?>
<?php if(can('service.view')): ?><a class="menu-item <?=active_page('maintenance',$current)?>" href="<?=page_url('maintenance')?>">⚙ Service Berkala</a><?php endif; ?>
<?php if(can('logistics.view')): ?><a class="menu-item <?=active_page('logistics',$current)?>" href="<?=page_url('logistics')?>">▤ Logistik & Spare Part</a><?php endif; ?>
<?php if(can('logistics.approve')): ?><a class="menu-item <?=active_page('approvals',$current)?>" href="<?=page_url('approvals')?>">✓ Approval Spare Part</a><?php endif; ?>
<?php if(can('documents.view')): ?><a class="menu-item <?=active_page('documents',$current)?>" href="<?=page_url('documents')?>">▧ Dokumen & Foto</a><?php endif; ?>
<div class="menu-title">Inspeksi & Operasi</div>
<?php if(can('inspections.view')): ?>
<a class="menu-item <?=active_page('tires',$current)?>" href="<?=page_url('tires')?>">◉ Inspeksi Ban</a>
<a class="menu-item <?=active_page('grease',$current)?>" href="<?=page_url('grease')?>">◆ Status Grease</a>
<a class="menu-item <?=active_page('mechanics',$current)?>" href="<?=page_url('mechanics')?>">♧ Jam Mekanik</a>
<a class="menu-item <?=active_page('condition',$current)?>" href="<?=page_url('condition')?>">▥ Condition Monitoring</a>
<?php endif; ?>
<div class="menu-title">Analisis & Laporan</div>
<?php if(can('analytics.view')): ?><a class="menu-item <?=active_page('location-dashboard',$current)?>" href="<?=page_url('location-dashboard')?>">⌖ Dashboard Lokasi</a><a class="menu-item <?=active_page('analytics',$current)?>" href="<?=page_url('analytics')?>">▦ KPI & Analitik</a><?php endif; ?>
<?php if(can('reports.export')): ?><a class="menu-item <?=active_page('reports',$current)?>" href="<?=page_url('reports')?>">⇩ Export Excel/PDF</a><?php endif; ?>
<?php if(can('qr.view')): ?><a class="menu-item <?=active_page('qr',$current)?>" href="<?=page_url('qr')?>">▣ QR Code Unit</a><?php endif; ?>
<?php if(can('history.view')): ?><a class="menu-item <?=active_page('history',$current)?>" href="<?=page_url('history')?>">↻ Riwayat Status</a><?php endif; ?>
<div class="menu-title">Sistem</div>
<a class="menu-item <?=active_page('notifications',$current)?>" href="<?=page_url('notifications')?>">🔔 Notifikasi</a>
<?php if(can('users.manage')): ?><a class="menu-item <?=active_page('users',$current)?>" href="<?=page_url('users')?>">♙ Pengguna & Hak Akses</a><?php endif; ?>
<?php if(can('audit.view')): ?><a class="menu-item <?=active_page('audit',$current)?>" href="<?=page_url('audit')?>">☷ Audit Trail</a><?php endif; ?>
<?php if(can('backup.manage')): ?><a class="menu-item <?=active_page('backup',$current)?>" href="<?=page_url('backup')?>">⤓ Backup Database</a><?php endif; ?>
</aside>
