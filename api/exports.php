<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/ExportService.php';
$db=Database::getInstance();
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET') api_json_response(405,['status'=>'error','message'=>'Metode tidak didukung.']);
$module=strtolower(trim((string)($_GET['module']??'')));
$period=trim((string)($_GET['period']??''))?:null;
$locationLabel=trim((string)($_GET['location']??''))?:null;
$rows=[];

switch($module){
case 'work-orders':
    api_require_permission('dashboard.read'); $scope=api_location_scope_clause('a','export_wo_location');
    $sql="SELECT w.wo_id,w.asset_id,a.asset_code,l.location_name,w.status,w.priority,w.maintenance_type,w.assigned_mechanic,w.issue_description,w.root_cause,w.repair_action,w.parts_requirement,w.reported_at,w.closed_at FROM work_orders w INNER JOIN assets a ON a.asset_id=w.asset_id LEFT JOIN locations l ON l.location_id=w.location_id";
    if($scope['sql']!=='')$sql.=' WHERE '.$scope['sql']; $stmt=$db->prepare($sql.' ORDER BY w.reported_at DESC');$stmt->execute($scope['params']);$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);break;
case 'pm-forecast':
    api_require_permission('dashboard.read'); $scope=api_location_scope_clause('a','export_pm_location');
    $sql="SELECT p.pm_plan_id,p.asset_id,a.asset_code,l.location_name,p.status,p.current_smr,p.last_service_hm,p.interval_hm,p.target_due_hm,p.original_estimated_date,p.revised_estimated_date,p.forecast_revision_no,p.planner_note FROM pm_plans p INNER JOIN assets a ON a.asset_id=p.asset_id LEFT JOIN locations l ON l.location_id=a.current_location_id";
    if($scope['sql']!=='')$sql.=' WHERE '.$scope['sql'];$stmt=$db->prepare($sql.' ORDER BY COALESCE(p.revised_estimated_date,p.original_estimated_date),p.target_due_hm');$stmt->execute($scope['params']);$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);break;
case 'unfulfilled-parts':
    api_require_permission('logistics.read'); $scope=api_location_scope_clause('a','export_part_location');
    $sql="SELECT pri.spb_id,pr.wo_id,pr.asset_id,a.asset_code,l.location_name,pri.part_number,COALESCE(p.technical_name,p.part_name,pri.description) technical_name,pri.qty_requested,pri.transaction_uom,COALESCE(pri.base_quantity,pri.qty_requested) base_quantity,COALESCE(pri.fulfilled_quantity,0) fulfilled_quantity,DATEDIFF(CURDATE(),DATE(pr.requested_at)) aging_calendar_days,pri.status FROM purchase_request_items pri INNER JOIN purchase_requests pr ON pr.spb_id=pri.spb_id LEFT JOIN assets a ON a.asset_id=pr.asset_id LEFT JOIN locations l ON l.location_id=a.current_location_id LEFT JOIN parts p ON p.part_number=pri.part_number WHERE pr.status NOT IN ('Cancelled','Closed') AND COALESCE(pri.base_quantity,pri.qty_requested)>COALESCE(pri.fulfilled_quantity,0)";
    if($scope['sql']!=='')$sql.=' AND '.$scope['sql'];$stmt=$db->prepare($sql.' ORDER BY pr.requested_at');$stmt->execute($scope['params']);$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);break;
case 'costs':
    api_require_permission('cost.read');
    $rows=$db->query('SELECT transaction_date,asset_id,wo_id,po_number,category,description,vendor,quantity,unit_cost,total_cost,verified FROM asset_cost_transactions ORDER BY transaction_date DESC,cost_id DESC')->fetchAll(PDO::FETCH_ASSOC);break;
case 'asset-valuation':
    api_require_permission('asset_valuation.read');
    $rows=$db->query('SELECT * FROM unit_valuations ORDER BY asset_id')->fetchAll(PDO::FETCH_ASSOC);break;
default: api_json_response(422,['status'=>'error','message'=>'Modul ekspor tidak didukung.']);
}
ExportService::download($rows,$module,$period,$locationLabel);
