<?php
require __DIR__.'/../app/bootstrap.php';
require_login();
require_permission('maintenance.edit');
verify_csrf();
$id=(int)($_POST['id']??0);
$stmt=$pdo->prepare('SELECT * FROM maintenance_orders WHERE id=?');$stmt->execute([$id]);$old=$stmt->fetch();
if(!$old){flash('error','Work Order tidak ditemukan.');redirect('../'.page_url('maintenance'));}
$status=$_POST['status']??'';
$priority=$_POST['priority']??'';
$allowedStatuses=['Open','Dalam Proses','Menunggu Spare Part','Selesai','Dibatalkan'];
$allowedPriorities=['Rendah','Sedang','Tinggi','Darurat'];
if(!in_array($status,$allowedStatuses,true)||!in_array($priority,$allowedPriorities,true)){flash('error','Status atau prioritas tidak valid.');redirect('../'.page_url('maintenance',['edit_wo'=>$id]));}
$mechanicId=($_POST['mechanic_id']??'')!==''?(int)$_POST['mechanic_id']:null;
$completed=$status==='Selesai'?($old['completed_at']?:date('Y-m-d H:i:s')):null;
$stmt=$pdo->prepare('UPDATE maintenance_orders SET service_date=?,start_datetime=?,end_datetime=?,maintenance_type=?,description=?,priority=?,mechanic_id=?,status=?,actual_cost=?,completed_at=?,meter_at_service=?,downtime_hours=?,estimated_hours=?,work_notes=? WHERE id=?');
$stmt->execute([$_POST['service_date']?:null,$_POST['start_datetime']?:null,$_POST['end_datetime']?:null,$_POST['maintenance_type'],trim($_POST['description']),$priority,$mechanicId,$status,(float)($_POST['actual_cost']??0),$completed,(float)($_POST['meter_at_service']??0),(float)($_POST['downtime_hours']??0),(float)($_POST['estimated_hours']??0),trim($_POST['work_notes']??''),$id]);
if($old['status']!==$status){add_history($pdo,'maintenance_order',$id,$old['status'],$status,trim($_POST['change_notes']??'Perubahan status WO'));}
audit($pdo,'UPDATE','maintenance_order',$id,'Memperbarui '.$old['work_order_no'].'; status '.$old['status'].' menjadi '.$status);
flash('success','Work Order '.$old['work_order_no'].' berhasil diperbarui.');
redirect('../'.page_url('maintenance'));
