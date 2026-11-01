<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/PartUnitConversion.php';
$db = Database::getInstance();
api_require_permission('logistics.write');
$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'GET') {
    $batch = trim((string)($_GET['batch_key'] ?? ''));
    if ($batch === '') api_json_response(422, ['status'=>'error','message'=>'batch_key wajib diisi.']);
    $stmt=$db->prepare('SELECT * FROM part_import_staging WHERE batch_key=:batch ORDER BY row_number');
    $stmt->execute([':batch'=>$batch]);
    $rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
    api_json_response(200,['status'=>'success','data'=>$rows,'summary'=>array_count_values(array_column($rows,'validation_status'))]);
}
if ($method !== 'POST') api_json_response(405,['status'=>'error','message'=>'Metode tidak didukung.']);
$input=json_decode((string)file_get_contents('php://input'),true);
if (!is_array($input)) api_json_response(422,['status'=>'error','message'=>'JSON tidak valid.']);
$action=strtolower(trim((string)($input['action'] ?? 'preview')));
$batch=trim((string)($input['batch_key'] ?? '')) ?: 'PART-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));

if ($action === 'commit') {
    $db->beginTransaction();
    try {
        $rows=$db->prepare("SELECT * FROM part_import_staging WHERE batch_key=:batch AND validation_status='VALID' ORDER BY row_number FOR UPDATE");
        $rows->execute([':batch'=>$batch]);
        $upsert=$db->prepare("INSERT INTO parts (part_number,part_name,technical_name,authenticity,part_group,category,unit_measure,base_uom,transaction_uom,conversion_factor,stock_qty,location_warehouse)
            VALUES (:sku,:name,:name,:auth,:group_name,'Other',:base_uom,:base_uom,:transaction_uom,:factor,:stock,:warehouse)
            ON DUPLICATE KEY UPDATE technical_name=VALUES(technical_name),authenticity=VALUES(authenticity),part_group=VALUES(part_group),base_uom=VALUES(base_uom),transaction_uom=VALUES(transaction_uom),conversion_factor=VALUES(conversion_factor),location_warehouse=VALUES(location_warehouse)");
        $count=0;
        foreach($rows->fetchAll(PDO::FETCH_ASSOC) as $row){
            $upsert->execute([':sku'=>$row['sku'],':name'=>$row['technical_name'],':auth'=>PartUnitConversion::normalizeAuthenticity((string)$row['authenticity']),':group_name'=>$row['part_group'] ?: null,':base_uom'=>$row['base_uom'],':transaction_uom'=>$row['transaction_uom'] ?: $row['base_uom'],':factor'=>PartUnitConversion::validateFactor((float)$row['conversion_factor']),':stock'=>(float)($row['opening_balance'] ?? 0),':warehouse'=>$row['warehouse_location'] ?: 'Gudang Yard KM 12']);
            $count++;
        }
        $db->commit();
        api_json_response(200,['status'=>'success','message'=>"{$count} part valid diimpor.",'data'=>['batch_key'=>$batch,'imported'=>$count]]);
    } catch(Throwable $e){ if($db->inTransaction())$db->rollBack(); api_json_response($e instanceof InvalidArgumentException?422:500,['status'=>'error','message'=>$e->getMessage()]); }
}

$records=is_array($input['records'] ?? null)?$input['records']:[];
if(!$records) api_json_response(422,['status'=>'error','message'=>'records wajib berisi minimal satu baris.']);
$db->beginTransaction();
try {
    $db->prepare('DELETE FROM part_import_staging WHERE batch_key=:batch')->execute([':batch'=>$batch]);
    $existing=$db->query('SELECT part_number FROM parts')->fetchAll(PDO::FETCH_COLUMN);
    $known=array_fill_keys(array_map('strtoupper',$existing),true); $seen=[]; $result=[];
    $insert=$db->prepare('INSERT INTO part_import_staging (batch_key,row_number,sku,technical_name,authenticity,part_group,base_uom,transaction_uom,conversion_factor,warehouse_location,opening_balance,validation_status,validation_errors,imported_by) VALUES (:batch,:row,:sku,:name,:auth,:group_name,:base_uom,:transaction_uom,:factor,:warehouse,:balance,:status,:errors,:actor)');
    foreach($records as $index=>$record){
        $sku=strtoupper(trim((string)($record['sku'] ?? $record['part_number'] ?? ''))); $name=trim((string)($record['technical_name'] ?? '')); $base=trim((string)($record['base_uom'] ?? '')); $errors=[];
        if($sku===''||preg_match('/^[A-Z0-9._\/-]{3,100}$/',$sku)!==1)$errors[]='SKU tidak valid';
        if($name==='')$errors[]='Nama teknis wajib'; if($base==='')$errors[]='Base UOM wajib';
        try{$auth=PartUnitConversion::normalizeAuthenticity((string)($record['authenticity'] ?? ''));}catch(Throwable $e){$auth='UNVERIFIED';$errors[]=$e->getMessage();}
        try{$factor=PartUnitConversion::validateFactor((float)($record['conversion_factor'] ?? 1));}catch(Throwable $e){$factor=1;$errors[]=$e->getMessage();}
        $duplicate=isset($seen[$sku])||isset($known[$sku]); $status=$duplicate?'DUPLICATE':($errors?'INVALID':'VALID'); $seen[$sku]=true;
        $insert->execute([':batch'=>$batch,':row'=>$index+1,':sku'=>$sku?:null,':name'=>$name?:null,':auth'=>$auth,':group_name'=>trim((string)($record['part_group']??''))?:null,':base_uom'=>$base?:null,':transaction_uom'=>trim((string)($record['transaction_uom']??''))?:$base,':factor'=>$factor,':warehouse'=>trim((string)($record['warehouse_location']??''))?:null,':balance'=>(float)($record['opening_balance']??0),':status'=>$status,':errors'=>$errors?implode('; ',$errors):null,':actor'=>(int)(api_current_user()['id']??0)?:null]);
        $result[]=['row_number'=>$index+1,'sku'=>$sku,'status'=>$status,'errors'=>$errors];
    }
    $db->commit(); api_json_response(200,['status'=>'success','data'=>$result,'batch_key'=>$batch,'summary'=>array_count_values(array_column($result,'status'))]);
} catch(Throwable $e){if($db->inTransaction())$db->rollBack();api_json_response(500,['status'=>'error','message'=>'Preview impor gagal.']);}
