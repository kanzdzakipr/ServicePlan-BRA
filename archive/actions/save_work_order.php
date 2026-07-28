<?php
require __DIR__.'/../app/bootstrap.php';
require_login();
require_permission('maintenance.edit');
verify_csrf();

$unitId=(int)($_POST['unit_id']??0);
$mechanicId=($_POST['mechanic_id']??'')!==''?(int)$_POST['mechanic_id']:null;
$serviceDate=$_POST['service_date']??date('Y-m-d');
$start=trim($_POST['start_datetime']??'');
$end=trim($_POST['end_datetime']??'');
$status=$_POST['status']??'Open';
$allowedStatuses=['Open','Dalam Proses','Menunggu Spare Part','Selesai','Dibatalkan'];
$allowedPriorities=['Rendah','Sedang','Tinggi','Darurat'];
if(!$unitId || !in_array($status,$allowedStatuses,true) || !in_array($_POST['priority']??'',$allowedPriorities,true)){
 flash('error','Data Work Order tidak valid.'); redirect('../'.page_url('maintenance'));
}
$wo=generate_number($pdo,'WO','maintenance_orders','work_order_no');
$stmt=$pdo->prepare('INSERT INTO maintenance_orders(unit_id,work_order_no,service_date,start_datetime,end_datetime,maintenance_type,description,priority,mechanic_id,status,actual_cost,requested_by,completed_at,meter_at_service,downtime_hours,estimated_hours,work_notes) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
$completed=$status==='Selesai'?date('Y-m-d H:i:s'):null;
$stmt->execute([$unitId,$wo,$serviceDate,$start?:null,$end?:null,$_POST['maintenance_type']??'Corrective',trim($_POST['description']??''),$_POST['priority'],$mechanicId,$status,(float)($_POST['actual_cost']??0),current_user()['id'],$completed,(float)($_POST['meter_at_service']??0),(float)($_POST['downtime_hours']??0),(float)($_POST['estimated_hours']??0),trim($_POST['work_notes']??'')]);
$id=(int)$pdo->lastInsertId();
audit($pdo,'CREATE','maintenance_order',$id,'Membuat '.$wo.' dengan prioritas '.$_POST['priority']);
add_history($pdo,'maintenance_order',$id,null,$status,'Work order dibuat');
flash('success','Work order '.$wo.' berhasil dibuat.');
redirect('../'.page_url('maintenance'));
