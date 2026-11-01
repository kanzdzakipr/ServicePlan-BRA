<?php
require_once 'db.php';
require_once dirname(__DIR__) . '/core/ReportIntegration.php';
require_once dirname(__DIR__) . '/core/PmForecast.php';

$db = Database::getInstance();
ReportIntegration::ensureTables($db);
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        api_require_permission('dashboard.read');
        $scope = api_location_scope_clause('a', 'pm_location_id');
        $sql = "
            SELECT p.*, a.asset_code, a.category AS asset_category, a.make_model,
                   a.year_manufacture, a.serial_number, a.license_plate, a.hull_number, a.alias_name, l.location_name,
                   a.current_operator_name, a.last_service_date AS asset_last_service_date, a.last_service_hm_km AS asset_last_service_hm_km,
                   a.service_interval_val, a.service_interval_unit, a.daily_usage_coefficient,
                   a.next_service_due_hm_km, a.next_service_estimated_date, a.service_manual_pdf_url,
                   (SELECT rpi.report_id
                    FROM report_pm_integrations rpi
                    WHERE rpi.pm_plan_id = p.pm_plan_id AND rpi.reversed_at IS NULL
                    ORDER BY rpi.integration_id DESC LIMIT 1) AS source_report_id,
                   (SELECT rr.report_number
                    FROM report_pm_integrations rpi
                    INNER JOIN report_records rr ON rr.report_id = rpi.report_id
                    WHERE rpi.pm_plan_id = p.pm_plan_id AND rpi.reversed_at IS NULL
                    ORDER BY rpi.integration_id DESC LIMIT 1) AS source_report_number
            FROM pm_plans p 
            INNER JOIN assets a ON p.asset_id = a.asset_id
            LEFT JOIN locations l ON l.location_id = a.current_location_id
        ";
        $filters = [];
        if ($scope['sql'] !== '') $filters[] = $scope['sql'];
        $params = $scope['params'];
        $statusFilter = strtoupper(trim((string)($_GET['status'] ?? '')));
        if ($statusFilter !== '') { $filters[]='p.status=:pm_status'; $params[':pm_status']=$statusFilter; }
        $monthFilter = trim((string)($_GET['month'] ?? ''));
        if ($monthFilter !== '') {
            if (preg_match('/^\d{4}-\d{2}$/',$monthFilter)!==1) api_json_response(422,['status'=>'error','message'=>'Filter bulan harus YYYY-MM.']);
            $filters[]="DATE_FORMAT(COALESCE(p.revised_estimated_date,p.original_estimated_date,a.next_service_estimated_date),'%Y-%m')=:pm_month";
            $params[':pm_month']=$monthFilter;
        }
        if ($filters) $sql .= ' WHERE ' . implode(' AND ', $filters);
        $sql .= " ORDER BY p.target_due_hm ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll();
        foreach($result as &$plan){
            $plan['quality_flags']=PmForecast::qualityFlags($plan['current_smr']??null,$plan['interval_hm']??null,$plan['daily_usage_coefficient']??null);
            $history=$db->prepare('SELECT adjustment_id,adjustment_type,original_estimated_date,revised_estimated_date,target_meter,current_meter,usage_rate,effective_from,effective_to,status_source,reason,created_by,created_at FROM pm_forecast_adjustments WHERE pm_plan_id=:id ORDER BY created_at DESC,adjustment_id DESC');
            $history->execute([':id'=>$plan['pm_plan_id']]); $plan['forecast_adjustments']=$history->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($plan);
        echo json_encode(["status" => "success", "data" => $result]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!is_array($data)) api_json_response(422,['status'=>'error','message'=>'JSON tidak valid.']);
        $action=strtolower(trim((string)($data['action']??'create')));

        if($action==='adjust_forecast'){
            $planId=(int)($data['pm_plan_id']??0); if($planId<1) api_json_response(422,['status'=>'error','message'=>'pm_plan_id wajib diisi.']);
            $type=PmForecast::adjustmentType((string)($data['adjustment_type']??'')); $reason=PmForecast::requireReason((string)($data['reason']??''));
            $revised=trim((string)($data['revised_estimated_date']??'')); $parsed=DateTimeImmutable::createFromFormat('!Y-m-d',$revised);
            if(!$parsed||$parsed->format('Y-m-d')!==$revised) api_json_response(422,['status'=>'error','message'=>'Tanggal revisi tidak valid.']);
            $db->beginTransaction();
            try{
                $lock=$db->prepare('SELECT p.*,a.next_service_estimated_date FROM pm_plans p INNER JOIN assets a ON a.asset_id=p.asset_id WHERE p.pm_plan_id=:id FOR UPDATE');$lock->execute([':id'=>$planId]);$plan=$lock->fetch(PDO::FETCH_ASSOC);
                if(!$plan)throw new OutOfBoundsException('PM plan tidak ditemukan.'); api_require_asset_access($db,(string)$plan['asset_id']);
                $original=$plan['original_estimated_date']?:$plan['next_service_estimated_date'];
                $insert=$db->prepare('INSERT INTO pm_forecast_adjustments (pm_plan_id,asset_id,adjustment_type,original_estimated_date,revised_estimated_date,target_meter,current_meter,usage_rate,effective_from,effective_to,status_source,reason,created_by) VALUES (:plan,:asset,:type,:original,:revised,:target,:current,:rate,:from_date,:to_date,:source,:reason,:actor)');
                $insert->execute([':plan'=>$planId,':asset'=>$plan['asset_id'],':type'=>$type,':original'=>$original?:null,':revised'=>$revised,':target'=>(float)$plan['target_due_hm'],':current'=>(float)$plan['current_smr'],':rate'=>isset($data['usage_rate'])?(float)$data['usage_rate']:null,':from_date'=>$data['effective_from']??null,':to_date'=>$data['effective_to']??null,':source'=>trim((string)($data['status_source']??''))?:null,':reason'=>$reason,':actor'=>(int)(api_current_user()['id']??0)?:null]);
                $db->prepare('UPDATE pm_plans SET original_estimated_date=COALESCE(original_estimated_date,:original),revised_estimated_date=:revised,forecast_revision_no=forecast_revision_no+1 WHERE pm_plan_id=:id')->execute([':original'=>$original?:null,':revised'=>$revised,':id'=>$planId]);
                $db->commit();api_json_response(200,['status'=>'success','message'=>'Forecast direvisi tanpa mengubah target meter.','data'=>['pm_plan_id'=>$planId,'target_meter'=>(float)$plan['target_due_hm'],'original_estimated_date'=>$original,'revised_estimated_date'=>$revised]]);
            }catch(Throwable $e){if($db->inTransaction())$db->rollBack();api_json_response($e instanceof OutOfBoundsException?404:422,['status'=>'error','message'=>$e->getMessage()]);}
        }

        if(in_array($action,['preview_next_month','publish_next_month'],true)){
            $month=trim((string)($data['month']??date('Y-m',strtotime('first day of next month'))));
            if(preg_match('/^\d{4}-\d{2}$/',$month)!==1)api_json_response(422,['status'=>'error','message'=>'Bulan harus YYYY-MM.']);
            $scope=api_location_scope_clause('a','pm_generate_location');
            $sql="SELECT a.asset_id,a.asset_code,a.last_hm_km,a.last_service_hm_km,a.last_service_date,a.service_interval_val,a.daily_usage_coefficient,a.next_service_due_hm_km,a.next_service_estimated_date FROM assets a WHERE a.is_active=1";
            if($scope['sql']!=='')$sql.=' AND '.$scope['sql'];$stmt=$db->prepare($sql.' ORDER BY a.asset_code,a.asset_id');$stmt->execute($scope['params']);$assets=$stmt->fetchAll(PDO::FETCH_ASSOC);$proposals=[];
            foreach($assets as $asset){
                $flags=PmForecast::qualityFlags($asset['last_hm_km'],$asset['service_interval_val'],$asset['daily_usage_coefficient']);
                $target=(float)($asset['next_service_due_hm_km']?:((float)$asset['last_service_hm_km']+(float)$asset['service_interval_val']));
                $estimate=$asset['next_service_estimated_date']?:PmForecast::estimate(date('Y-m-d'),(float)$asset['last_hm_km'],$target,(float)$asset['daily_usage_coefficient']);
                $proposals[]=['generation_key'=>$month.':'.$asset['asset_id'],'asset_id'=>$asset['asset_id'],'asset_code'=>$asset['asset_code'],'current_meter'=>(float)$asset['last_hm_km'],'target_meter'=>$target,'estimated_date'=>$estimate,'quality_flags'=>$flags,'publishable'=>!$flags];
            }
            if($action==='preview_next_month')api_json_response(200,['status'=>'success','mode'=>'preview','data'=>$proposals]);
            $db->beginTransaction();$created=0;$skipped=0;
            try{$check=$db->prepare('SELECT pm_plan_id FROM pm_plans WHERE generation_key=:key LIMIT 1');$insert=$db->prepare("INSERT INTO pm_plans (asset_id,interval_hm,current_smr,last_service_hm,last_service_date,target_due_hm,original_estimated_date,status,planner_note,generation_key) VALUES (:asset,:interval,:current,:last_meter,:last_date,:target,:estimate,'PLANNED',:note,:generation_key)");
                foreach($proposals as $proposal){if(!$proposal['publishable']){$skipped++;continue;}$check->execute([':key'=>$proposal['generation_key']]);if($check->fetchColumn()){$skipped++;continue;}$asset=$assets[array_search($proposal['asset_id'],array_column($assets,'asset_id'),true)];$insert->execute([':asset'=>$proposal['asset_id'],':interval'=>(int)$asset['service_interval_val'],':current'=>$proposal['current_meter'],':last_meter'=>(float)$asset['last_service_hm_km'],':last_date'=>$asset['last_service_date'],':target'=>$proposal['target_meter'],':estimate'=>$proposal['estimated_date'],':note'=>'Generated idempotently for '.$month,':generation_key'=>$proposal['generation_key']]);$created++;}
                $db->commit();api_json_response(200,['status'=>'success','mode'=>'publish','data'=>['month'=>$month,'created'=>$created,'skipped'=>$skipped]]);
            }catch(Throwable $e){if($db->inTransaction())$db->rollBack();api_json_response(500,['status'=>'error','message'=>'Generator PM gagal dipublikasikan.']);}
        }

        if (!$data || !isset($data['asset_id']) || !isset($data['interval_hm'])) {
            echo json_encode(["status" => "error", "message" => "Invalid data"]);
            exit;
        }
        api_require_asset_access($db, (string) $data['asset_id']);

        $stmt = $db->prepare("
            INSERT INTO pm_plans (
                asset_id, interval_hm, current_smr, last_service_hm, 
                last_service_date, target_due_hm, variance_hm, status, 
                warranty_status, planner_note
            ) VALUES (
                :asset_id, :interval, :smr, :last_hm, 
                :last_date, :target, :variance, :status, 
                :warranty, :note
            )
        ");
        
        try {
            $stmt->execute([
                ':asset_id' => $data['asset_id'],
                ':interval' => $data['interval_hm'],
                ':smr' => $data['current_smr'] ?? 0,
                ':last_hm' => $data['last_service_hm'] ?? 0,
                ':last_date' => $data['last_service_date'] ?? date('Y-m-d'),
                ':target' => $data['target_due_hm'] ?? 0,
                ':variance' => $data['variance_hm'] ?? 0,
                ':status' => $data['status'] ?? 'PLANNED',
                ':warranty' => $data['warranty_status'] ?? 'No Warranty',
                ':note' => $data['planner_note'] ?? ''
            ]);
            echo json_encode(["status" => "success", "message" => "PM Plan created successfully"]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method not allowed"]);
        break;
}
?>
