<?php
// Dashboard lengkap: semua angka dibaca dari database aktif.
$stats = [
    'total_units' => (int)$pdo->query("SELECT COUNT(*) FROM units")->fetchColumn(),
    'active_units' => (int)$pdo->query("SELECT COUNT(*) FROM units WHERE operational_status='Ready for Use'")->fetchColumn(),
    'repair_units' => (int)$pdo->query("SELECT COUNT(*) FROM units WHERE operational_status IN ('Breakdown','Downtime','Maintenance')")->fetchColumn(),
    'repair_cost' => (float)$pdo->query("SELECT COALESCE(SUM(actual_cost),0) FROM maintenance_orders WHERE MONTH(service_date)=MONTH(CURDATE()) AND YEAR(service_date)=YEAR(CURDATE())")->fetchColumn(),
    'open_logistics' => (int)$pdo->query("SELECT COUNT(*) FROM logistics_orders WHERE status NOT IN ('Tiba','Dibatalkan')")->fetchColumn(),
];

$woSummary = $pdo->query("SELECT
    COUNT(*) total,
    SUM(status='Open') open_count,
    SUM(status='Dalam Proses') process_count,
    SUM(status='Menunggu Spare Part') waiting_count,
    SUM(status='Selesai') completed_count,
    SUM(priority IN ('Tinggi','Darurat') AND status NOT IN ('Selesai','Dibatalkan')) AS high_priority_count,
    COALESCE(SUM(downtime_hours),0) total_downtime
    FROM maintenance_orders")->fetch() ?: [];

$conditionMap=['Breakdown'=>0,'Ready for Use'=>0,'Downtime'=>0];
foreach($pdo->query("SELECT status,COUNT(*) total FROM condition_events WHERE event_date >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) GROUP BY status") as $r){
    $conditionMap[$r['status']] = (int)$r['total'];
}

$categoryLabels=[];$categoryValues=[];
foreach($pdo->query("SELECT category,COUNT(*) total FROM units GROUP BY category ORDER BY total DESC LIMIT 7") as $r){
    $categoryLabels[]=$r['category'];$categoryValues[]=(int)$r['total'];
}

$serviceCounts=['Terlambat'=>0,'Jatuh Tempo'=>0,'Akan Service'=>0,'Terjadwal'=>0,'Selesai'=>0];
foreach($pdo->query("SELECT status,COUNT(*) total FROM service_schedules GROUP BY status") as $r){
    $serviceCounts[$r['status']] = (int)$r['total'];
}
$serviceRows=$pdo->query("SELECT u.unit_code,u.unit_name,u.current_meter,u.meter_type,ss.next_service_meter,ss.next_service_date,ss.status,ss.notes
    FROM service_schedules ss JOIN units u ON u.id=ss.unit_id
    ORDER BY FIELD(ss.status,'Terlambat','Jatuh Tempo','Akan Service','Terjadwal','Selesai'),ss.next_service_date LIMIT 8")->fetchAll();

$logisticsCounts=['Dipesan'=>0,'Diproses Vendor'=>0,'Dalam Pengiriman'=>0,'Tiba'=>0,'Tertunda'=>0,'Dibatalkan'=>0];
foreach($pdo->query("SELECT status,COUNT(*) total FROM logistics_orders GROUP BY status") as $r){
    $logisticsCounts[$r['status']] = (int)$r['total'];
}

$costRows=$pdo->query("SELECT u.unit_code,u.unit_name,SUM(m.actual_cost) total
    FROM maintenance_orders m JOIN units u ON u.id=m.unit_id
    GROUP BY u.id ORDER BY total DESC LIMIT 6")->fetchAll();

$workOrders=$pdo->query("SELECT m.id,m.work_order_no,u.unit_code,m.maintenance_type,m.priority,
    COALESCE(me.name,'Belum ditentukan') mechanic_name,m.status,m.downtime_hours,m.actual_cost,m.service_date
    FROM maintenance_orders m JOIN units u ON u.id=m.unit_id
    LEFT JOIN mechanics me ON me.id=m.mechanic_id
    ORDER BY FIELD(m.status,'Dalam Proses','Menunggu Spare Part','Open','Selesai','Dibatalkan'),m.id DESC LIMIT 7")->fetchAll();

$tireCounts = $pdo->query("SELECT
    COUNT(*) inspected,
    SUM(condition_status IN ('Perlu Rotasi','Aus Tidak Rata')) rotation_count,
    SUM(condition_status IN ('Tipis','Harus Diganti')) replacement_count
    FROM tire_inspections WHERE inspection_date >= DATE_SUB(CURDATE(),INTERVAL 30 DAY)")->fetch() ?: [];
$tireRows=$pdo->query("SELECT u.unit_code,t.tire_position,t.condition_status,t.pressure_psi
    FROM tire_inspections t JOIN units u ON u.id=t.unit_id ORDER BY t.inspection_date DESC,t.id DESC LIMIT 5")->fetchAll();

$greaseCounts=['Sesuai Jadwal'=>0,'Jatuh Tempo'=>0,'Terlambat'=>0,'Belum Ada Data'=>0];
foreach($pdo->query("SELECT status,COUNT(*) total FROM grease_records GROUP BY status") as $r){
    $greaseCounts[$r['status']] = (int)$r['total'];
}
$greaseRows=$pdo->query("SELECT u.unit_code,g.status,(u.current_meter-g.meter_at_grease) elapsed_meter
    FROM grease_records g JOIN units u ON u.id=g.unit_id ORDER BY g.grease_date DESC,g.id DESC LIMIT 5")->fetchAll();

$mechanicRows=$pdo->query("SELECT m.name,m.target_hours_month,COALESCE(SUM(h.effective_hours),0) actual_hours
    FROM mechanics m LEFT JOIN mechanic_hours h ON h.mechanic_id=m.id
      AND MONTH(h.work_date)=MONTH(CURDATE()) AND YEAR(h.work_date)=YEAR(CURDATE())
    WHERE m.is_active=1 GROUP BY m.id ORDER BY actual_hours DESC LIMIT 7")->fetchAll();
$mechanicLabels=array_column($mechanicRows,'name');
$mechanicActual=array_map('floatval',array_column($mechanicRows,'actual_hours'));
$mechanicTargets=array_map('floatval',array_column($mechanicRows,'target_hours_month'));

$costTrend=$pdo->query("SELECT DATE_FORMAT(service_date,'%Y-%m') ym,COALESCE(SUM(actual_cost),0) total
    FROM maintenance_orders WHERE service_date >= DATE_SUB(CURDATE(),INTERVAL 11 MONTH)
    GROUP BY ym ORDER BY ym")->fetchAll();
$costTrendLabels=[];$costTrendValues=[];
foreach($costTrend as $r){$costTrendLabels[]=$r['ym'];$costTrendValues[]=(float)$r['total'];}

$totalMaintenance=(int)$pdo->query("SELECT COUNT(*) FROM maintenance_orders WHERE MONTH(service_date)=MONTH(CURDATE()) AND YEAR(service_date)=YEAR(CURDATE())")->fetchColumn();
$completedMaintenance=(int)$pdo->query("SELECT COUNT(*) FROM maintenance_orders WHERE status='Selesai' AND MONTH(service_date)=MONTH(CURDATE()) AND YEAR(service_date)=YEAR(CURDATE())")->fetchColumn();
?>

<section class="kpis dashboard-kpis">
  <div class="card kpi"><div class="icon blue">▱</div><div><div class="label">Total Unit</div><div class="value"><?=number_format($stats['total_units'],0,',','.')?></div><div class="sub">Seluruh unit terdaftar</div></div></div>
  <div class="card kpi"><div class="icon green">✓</div><div><div class="label">Ready for Use</div><div class="value"><?=number_format($stats['active_units'],0,',','.')?></div><div class="sub"><?=$stats['total_units'] ? number_format($stats['active_units']/$stats['total_units']*100,1,',','.') : 0?>% dari total unit</div></div></div>
  <div class="card kpi"><div class="icon orange">⚙</div><div><div class="label">Dalam Perbaikan</div><div class="value"><?=number_format($stats['repair_units'],0,',','.')?></div><div class="sub">Breakdown, downtime, maintenance</div></div></div>
  <div class="card kpi"><div class="icon red">Rp</div><div><div class="label">Biaya Perbaikan Bulan Ini</div><div class="value small-value"><?=rupiah($stats['repair_cost'])?></div><div class="sub">Berdasarkan biaya aktual WO</div></div></div>
  <div class="card kpi"><div class="icon purple">▣</div><div><div class="label">Pesanan Logistik Aktif</div><div class="value"><?=number_format($stats['open_logistics'],0,',','.')?></div><div class="sub">Belum tiba atau dibatalkan</div></div></div>
</section>

<section class="dashboard-grid dashboard-grid-top">
  <div class="card panel">
    <h3>Unit per Kategori</h3>
    <div class="chart-wrap"><canvas id="assetCategoryChart" data-labels='<?=e(json_encode($categoryLabels))?>' data-values='<?=e(json_encode($categoryValues))?>'></canvas></div>
  </div>
  <div class="card panel">
    <h3>Status Perbaikan & Work Order</h3>
    <div class="chart-wrap"><canvas id="workOrderStatusChart" data-values='<?=e(json_encode([(int)($woSummary['completed_count']??0),(int)($woSummary['process_count']??0),(int)($woSummary['waiting_count']??0),(int)($woSummary['open_count']??0)]))?>'></canvas></div>
  </div>
  <div class="card panel">
    <h3>Status Pemesanan Barang Logistik</h3>
    <table><thead><tr><th>Status</th><th>Jumlah</th></tr></thead><tbody>
      <?php foreach(['Dipesan','Diproses Vendor','Dalam Pengiriman','Tiba','Tertunda'] as $s): ?>
      <tr><td><span class="status <?=status_class($s)?>"><?=e($s)?></span></td><td><b><?=$logisticsCounts[$s]?></b></td></tr>
      <?php endforeach; ?>
    </tbody></table>
    <a class="panel-link" href="<?=page_url('logistics')?>">Kelola logistik →</a>
  </div>
</section>

<section class="dashboard-grid dashboard-grid-mid">
  <div class="card panel">
    <h3>Work Order Aktif dan Terbaru</h3>
    <table><thead><tr><th>No. WO</th><th>Unit</th><th>Prioritas</th><th>Mekanik</th><th>Status</th><th>Biaya</th></tr></thead><tbody>
    <?php foreach($workOrders as $r): ?>
      <tr><td><a href="<?=page_url('maintenance',['edit_wo'=>$r['id']])?>#form-work-order"><?=e($r['work_order_no'])?></a></td><td><?=e($r['unit_code'])?></td><td><span class="status <?=in_array($r['priority'],['Tinggi','Darurat'],true)?'st-red':'st-orange'?>"><?=e($r['priority'])?></span></td><td><?=e($r['mechanic_name'])?></td><td><span class="status <?=status_class($r['status'])?>"><?=e($r['status'])?></span></td><td><?=rupiah($r['actual_cost'])?></td></tr>
    <?php endforeach; ?>
    </tbody></table>
    <div class="wo-summary-row">
      <span>Prioritas tinggi/darurat aktif: <b><?=number_format((int)($woSummary['high_priority_count']??0),0,',','.')?></b></span>
      <span>Total downtime WO: <b><?=number_format((float)($woSummary['total_downtime']??0),1,',','.')?> jam</b></span>
    </div>
  </div>
  <div class="card panel">
    <h3>Biaya Perbaikan per Unit</h3>
    <table><thead><tr><th>Unit</th><th>Total</th></tr></thead><tbody><?php foreach($costRows as $r): ?><tr><td><?=e($r['unit_code'])?></td><td><?=rupiah($r['total'])?></td></tr><?php endforeach; ?></tbody></table>
  </div>
  <div class="card panel">
    <h3>Ringkasan Aset & Maintenance</h3>
    <div class="mini-cards"><div class="mini">Total Unit<strong><?=$stats['total_units']?></strong></div><div class="mini">Unit Aktif<strong><?=$stats['active_units']?></strong></div><div class="mini">Dalam Perbaikan<strong><?=$stats['repair_units']?></strong></div></div>
    <div class="summary-list"><div class="row"><span>Total Maintenance Bulan Ini</span><b><?=$totalMaintenance?></b></div><div class="row"><span>Maintenance Selesai</span><b><?=$completedMaintenance?></b></div><div class="row"><span>WO Menunggu Spare Part</span><b><?=number_format((int)($woSummary['waiting_count']??0),0,',','.')?></b></div><div class="row"><span>WO Dalam Proses</span><b><?=number_format((int)($woSummary['process_count']??0),0,',','.')?></b></div></div>
  </div>
</section>

<section class="service-grid">
  <div class="card panel">
    <h3>Status Service Berkala Unit</h3>
    <div class="service-kpis">
      <div class="service-kpi"><span class="caption">Terlambat</span><span class="num text-red"><?=$serviceCounts['Terlambat']?></span></div>
      <div class="service-kpi"><span class="caption">Jatuh Tempo</span><span class="num text-orange"><?=$serviceCounts['Jatuh Tempo']?></span></div>
      <div class="service-kpi"><span class="caption">Akan Service</span><span class="num text-blue"><?=$serviceCounts['Akan Service']?></span></div>
      <div class="service-kpi"><span class="caption">Terjadwal</span><span class="num text-green"><?=$serviceCounts['Terjadwal']?></span></div>
      <div class="service-kpi"><span class="caption">Selesai</span><span class="num text-purple"><?=$serviceCounts['Selesai']?></span></div>
    </div>
    <table><thead><tr><th>Unit</th><th>Meter Aktual</th><th>Service Berikut</th><th>Selisih</th><th>Rencana</th><th>Status</th></tr></thead><tbody>
    <?php foreach($serviceRows as $r): $diff=(float)$r['next_service_meter']-(float)$r['current_meter']; ?>
      <tr><td><?=e($r['unit_code'])?></td><td><?=number_format($r['current_meter'],0,',','.')?> <?=e($r['meter_type'])?></td><td><?=number_format($r['next_service_meter'],0,',','.')?></td><td class="<?=$diff<0?'priority-high':($diff<=50?'priority-medium':'priority-low')?>"><?=number_format($diff,0,',','.')?> <?=e($r['meter_type'])?></td><td><?=e($r['next_service_date'])?></td><td><span class="status <?=status_class($r['status'])?>"><?=e($r['status'])?></span></td></tr>
    <?php endforeach; ?>
    </tbody></table>
  </div>
  <div class="card panel">
    <h3>Distribusi Status Service</h3>
    <div style="height:245px"><canvas id="serviceStatusChart" data-values='<?=e(json_encode(array_values($serviceCounts)))?>'></canvas></div>
    <div class="summary-list"><div class="row"><span>WO Service Belum Selesai</span><b><?=number_format((int)(($woSummary['total']??0)-($woSummary['completed_count']??0)),0,',','.')?></b></div><div class="row"><span>Spare Part Belum Tiba</span><b><?=$stats['open_logistics']?></b></div></div>
  </div>
</section>

<section class="analysis-grid">
  <div class="card panel">
    <h3>Analisis Inspeksi Ban</h3>
    <div class="mini-cards"><div class="mini">Ban Diperiksa<strong><?=number_format((int)($tireCounts['inspected']??0),0,',','.')?></strong></div><div class="mini">Perlu Rotasi<strong><?=number_format((int)($tireCounts['rotation_count']??0),0,',','.')?></strong></div><div class="mini">Harus Diganti<strong><?=number_format((int)($tireCounts['replacement_count']??0),0,',','.')?></strong></div></div>
    <table><thead><tr><th>Unit</th><th>Posisi</th><th>Kondisi</th><th>Tekanan</th></tr></thead><tbody><?php foreach($tireRows as $r): ?><tr><td><?=e($r['unit_code'])?></td><td><?=e($r['tire_position'])?></td><td><span class="status <?=status_class($r['condition_status'])?>"><?=e($r['condition_status'])?></span></td><td><?=e((string)$r['pressure_psi'])?> PSI</td></tr><?php endforeach; ?></tbody></table>
  </div>
  <div class="card panel">
    <h3>Status Grease Unit</h3>
    <?php $greaseTotal=max(1,array_sum($greaseCounts)); foreach($greaseCounts as $s=>$n): $pct=$n/$greaseTotal*100; ?>
      <div class="metric-line"><span><?=e($s)?></span><b><?=$n?> unit</b></div><div class="progress"><span style="width:<?=number_format($pct,1,'.','')?>%;background:<?=status_class($s)==='st-red'?'#ef4444':(status_class($s)==='st-orange'?'#ff9b21':'#20b26b')?>"></span></div>
    <?php endforeach; ?>
    <table class="compact-table"><thead><tr><th>Unit</th><th>Sejak Grease</th><th>Status</th></tr></thead><tbody><?php foreach($greaseRows as $r): ?><tr><td><?=e($r['unit_code'])?></td><td><?=number_format($r['elapsed_meter'],0,',','.')?></td><td><span class="status <?=status_class($r['status'])?>"><?=e($r['status'])?></span></td></tr><?php endforeach; ?></tbody></table>
  </div>
  <div class="card panel">
    <h3>Jam Kerja Aktual Mekanik</h3>
    <div style="height:225px"><canvas id="mechanicHoursChart" data-labels='<?=e(json_encode($mechanicLabels))?>' data-actual='<?=e(json_encode($mechanicActual))?>' data-targets='<?=e(json_encode($mechanicTargets))?>'></canvas></div>
    <table><thead><tr><th>Mekanik</th><th>Aktual</th><th>Target</th><th>Produktivitas</th></tr></thead><tbody><?php foreach($mechanicRows as $r): $prod=(float)$r['target_hours_month']>0?(float)$r['actual_hours']/(float)$r['target_hours_month']*100:0; ?><tr><td><?=e($r['name'])?></td><td><b><?=number_format($r['actual_hours'],1,',','.')?> jam</b></td><td><?=number_format($r['target_hours_month'],0,',','.')?> jam</td><td><span class="status <?=$prod>=100?'st-green':($prod>=85?'st-orange':'st-red')?>"><?=number_format($prod,0,',','.')?>%</span></td></tr><?php endforeach; ?></tbody></table>
  </div>
</section>

<section class="bottom-grid">
  <div class="card panel"><h3>Tren Biaya Perbaikan Bulanan</h3><div style="height:285px"><canvas id="costTrendChart" data-labels='<?=e(json_encode($costTrendLabels))?>' data-values='<?=e(json_encode($costTrendValues))?>'></canvas></div></div>
  <div class="card panel"><h3>Aksi Cepat</h3><div class="quick"><a href="<?=page_url('unit-form')?>">Tambah Unit Baru</a><a href="<?=page_url('maintenance')?>#form-work-order">Buat Work Order</a><a href="<?=page_url('logistics')?>">Buat Pesanan Logistik</a><a href="<?=page_url('condition')?>">Input Condition Monitoring</a><a href="<?=page_url('reports')?>">Buat Laporan</a></div></div>
</section>
