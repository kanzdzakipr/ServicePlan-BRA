<?php
$days=max(1,min(365,(int)($_GET['days']??30)));$periodHours=$days*24;
$sql="SELECT u.id,u.unit_code,u.unit_name,u.location,u.current_meter,u.meter_type,
COALESCE(SUM(CASE WHEN ce.status='Breakdown' THEN ce.duration_hours ELSE 0 END),0) breakdown_h,
COALESCE(SUM(CASE WHEN ce.status='Downtime' THEN ce.duration_hours ELSE 0 END),0) downtime_h,
COALESCE(SUM(CASE WHEN ce.status='Ready for Use' THEN ce.duration_hours ELSE 0 END),0) ready_h,
SUM(CASE WHEN ce.status='Breakdown' THEN 1 ELSE 0 END) breakdown_count,
COALESCE((SELECT SUM(m.actual_cost) FROM maintenance_orders m WHERE m.unit_id=u.id),0) cost
FROM units u LEFT JOIN condition_events ce ON ce.unit_id=u.id AND ce.event_date>=DATE_SUB(CURDATE(),INTERVAL $days DAY) GROUP BY u.id ORDER BY u.unit_code";
$rows=$pdo->query($sql)->fetchAll();
foreach($rows as &$r){$down=(float)$r['breakdown_h']+(float)$r['downtime_h'];$available=max(0,$periodHours-$down);$r['availability']=$periodHours?($available/$periodHours*100):0;$r['utilization']=$available?min(100,(float)$r['ready_h']/$available*100):0;$bc=(int)$r['breakdown_count'];$r['mtbf']=$bc?((float)$r['ready_h']/$bc):(float)$r['ready_h'];$r['mttr']=$bc?($down/$bc):0;$r['cost_per_meter']=(float)$r['current_meter']>0?(float)$r['cost']/(float)$r['current_meter']:0;}unset($r);
?>
<div class="page-actions"><div><h3>Availability, Utilization, MTBF, MTTR & Biaya per Meter</h3><p class="muted">Periode analisis <?=$days?> hari. Nilai bergantung pada kelengkapan input condition event.</p></div><form class="search-form"><input type="hidden" name="page" value="analytics"><select name="days"><option value="7">7 hari</option><option value="30" <?=$days===30?'selected':''?>>30 hari</option><option value="90" <?=$days===90?'selected':''?>>90 hari</option></select><button class="btn">Terapkan</button></form></div>
<div class="card panel"><table><thead><tr><th>Unit</th><th>Lokasi</th><th>Availability</th><th>Utilization</th><th>MTBF</th><th>MTTR</th><th>Total Biaya</th><th>Biaya/HM-KM</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=e($r['unit_code'])?></td><td><?=e($r['location'])?></td><td><?=number_format($r['availability'],1)?>%</td><td><?=number_format($r['utilization'],1)?>%</td><td><?=number_format($r['mtbf'],1)?> jam</td><td><?=number_format($r['mttr'],1)?> jam</td><td><?=rupiah($r['cost'])?></td><td><?=rupiah($r['cost_per_meter'])?>/<?=e($r['meter_type'])?></td></tr><?php endforeach;?></tbody></table></div>
<div class="alert alert-success">Rumus: Availability = (jam periode − breakdown − downtime) ÷ jam periode. Utilization = jam operasi tercatat ÷ jam tersedia. MTBF = jam operasi ÷ jumlah breakdown. MTTR = total jam perbaikan/downtime ÷ jumlah breakdown.</div>
