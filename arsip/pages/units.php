<?php
$q=trim($_GET['q']??'');
$sql='SELECT * FROM units'; $params=[];
if($q!==''){ $sql.=' WHERE unit_code LIKE ? OR unit_name LIKE ? OR category LIKE ?'; $params=["%$q%","%$q%","%$q%"]; }
$sql.=' ORDER BY unit_code'; $stmt=$pdo->prepare($sql);$stmt->execute($params);$rows=$stmt->fetchAll();
?>
<div class="page-actions"><form class="search-form"><input type="hidden" name="page" value="units"><input name="q" value="<?=e($q)?>" placeholder="Cari unit..."><button class="btn">Cari</button></form><a class="btn primary" href="<?=page_url('unit-form')?>">+ Tambah Unit</a></div>
<div class="card panel"><table><thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Lokasi</th><th>Meter</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?=e($r['unit_code'])?></td><td><?=e($r['unit_name'])?></td><td><?=e($r['category'])?></td><td><?=e($r['location'])?></td><td><?=number_format($r['current_meter'],0,',','.')?> <?=e($r['meter_type'])?></td><td><span class="status <?=status_class($r['operational_status'])?>"><?=e($r['operational_status'])?></span></td><td><a href="<?=page_url('unit-form',['id'=>$r['id']])?>">Edit</a> · <form class="inline" method="post" action="actions/delete_unit.php" onsubmit="return confirm('Hapus unit ini?')"><input type="hidden" name="_csrf" value="<?=csrf_token()?>"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="link danger">Hapus</button></form></td></tr><?php endforeach; ?>
</tbody></table></div>
