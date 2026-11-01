<?php
declare(strict_types=1);

require_once 'db.php';
$db=Database::getInstance(); api_require_permission('dashboard.read');
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET')api_json_response(405,['status'=>'error','message'=>'Metode tidak didukung.']);
$date=trim((string)($_GET['date']??date('Y-m-d')));if(DateTimeImmutable::createFromFormat('!Y-m-d',$date)?->format('Y-m-d')!==$date)api_json_response(422,['status'=>'error','message'=>'Tanggal tidak valid.']);
$scope=api_location_scope_clause('a','wo_recap_location');$where=['DATE(COALESCE(w.trouble_at,w.reported_at))<=:recap_start_date','(w.closed_at IS NULL OR DATE(w.closed_at)>=:recap_end_date)'];$params=array_merge([':recap_start_date'=>$date,':recap_end_date'=>$date,':timeline_date'=>$date],$scope['params']);if($scope['sql']!=='')$where[]=$scope['sql'];
foreach(['status'=>'w.status','asset_id'=>'w.asset_id'] as $key=>$column){$value=trim((string)($_GET[$key]??''));if($value!==''){$where[]="$column=:$key";$params[":$key"]=$value;}}
$mechanic=trim((string)($_GET['mechanic']??''));if($mechanic!==''){$where[]='(w.assigned_mechanic LIKE :mechanic OR w.assigned_mechanics_json LIKE :mechanic)';$params[':mechanic']='%'.$mechanic.'%';}
$sql="SELECT w.wo_id,w.asset_id,a.asset_code,a.hull_number,l.location_name,w.status,w.priority,w.maintenance_type,w.issue_description,w.root_cause,w.repair_action,w.assigned_mechanic,w.assigned_mechanics_json,w.parts_requirement,w.reported_at,w.repair_started_at,w.estimated_completion_at,w.closed_at,w.downtime_minutes,(SELECT wt.description FROM work_order_timeline wt WHERE wt.wo_id=w.wo_id AND wt.occurred_at<DATE_ADD(:timeline_date,INTERVAL 1 DAY) ORDER BY wt.occurred_at DESC,wt.timeline_id DESC LIMIT 1) latest_progress,(SELECT COUNT(*) FROM purchase_requests pr WHERE pr.wo_id=w.wo_id AND pr.status NOT IN ('Cancelled','Closed')) open_spb_count FROM work_orders w INNER JOIN assets a ON a.asset_id=w.asset_id LEFT JOIN locations l ON l.location_id=COALESCE(w.location_id,a.current_location_id) WHERE ".implode(' AND ',$where).' ORDER BY l.location_name,a.asset_code,w.priority DESC,w.wo_id';
$stmt=$db->prepare($sql);$stmt->execute($params);$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
api_json_response(200,['status'=>'success','date'=>$date,'data'=>$rows,'summary'=>['total'=>count($rows),'open'=>count(array_filter($rows,fn($r)=>!in_array($r['status'],['Closed','Cancelled'],true))),'waiting_part'=>count(array_filter($rows,fn($r)=>$r['status']==='Waiting Part'))]]);
