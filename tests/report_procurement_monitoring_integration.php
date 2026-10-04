<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/core/ReportIntegration.php';
$dsn=(string)(getenv('REPORT_TEST_DSN')?:'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
try{$db=new PDO($dsn,(string)(getenv('REPORT_TEST_DB_USER')?:'root'),(string)(getenv('REPORT_TEST_DB_PASSWORD')?:''),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);}catch(Throwable $e){echo "SKIP: {$e->getMessage()}\n";exit(0);}
ReportIntegration::ensureTables($db);$passed=0;
function batch10Assert(bool $ok,string $message):void{global $passed;if(!$ok)throw new RuntimeException("FAIL: {$message}");$passed++;echo "PASS: {$message}\n";}
$context=$db->query("SELECT w.wo_id,w.asset_id FROM work_orders w JOIN assets a ON a.asset_id=w.asset_id WHERE a.is_active=1 AND w.status NOT IN ('Closed','Cancelled') ORDER BY w.wo_id LIMIT 1")->fetch();
$part=$db->query('SELECT part_id,part_number,part_name,unit_measure,unit_cost,stock_qty FROM parts ORDER BY part_id LIMIT 1')->fetch();if(!$context||!$part){echo "SKIP: prerequisite data unavailable.\n";exit(0);}
$suffix=strtoupper(substr(bin2hex(random_bytes(8)),0,12));$spb='QA-MON-'.$suffix;$ppb='QA-PPB-MON-'.$suffix;$monitorReport='71717171-7171-4717-8717-'.$suffix;$weeklyReport='72727272-7272-4727-8727-'.$suffix;$date=(new DateTimeImmutable('today'))->format('Y-m-d');
$db->beginTransaction();try{
 $db->prepare("INSERT INTO purchase_requests(spb_id,wo_id,asset_id,requested_by,urgency,status,requested_at) VALUES(:spb,:wo,:asset,1,'Normal','Submitted',NOW())")->execute([':spb'=>$spb,':wo'=>$context['wo_id'],':asset'=>$context['asset_id']]);
 $requestItem='QA-MON-ITEM-'.$suffix;$db->prepare("INSERT INTO purchase_request_items(id,spb_id,part_number,description,qty_requested,status) VALUES(:id,:spb,:part,:description,2,'Menunggu Approval')")->execute([':id'=>$requestItem,':spb'=>$spb,':part'=>$part['part_number'],':description'=>$part['part_name']]);
 $db->prepare("INSERT INTO purchase_orders(ppb_id,spb_id,asset_id,wo_id,vendor,project,delivery_due,delivery_location,subtotal,tax_amount,total_amount,status,created_by) VALUES(:ppb,:spb,:asset,:wo,'QA Vendor','QA Project',:due,'QA Yard',2000,220,2220,'Submitted',1)")->execute([':ppb'=>$ppb,':spb'=>$spb,':asset'=>$context['asset_id'],':wo'=>$context['wo_id'],':due'=>$date]);
 $db->prepare("INSERT INTO purchase_order_items(id,ppb_id,part_number,description,unit_measure,quantity,unit_price,total_price) VALUES(:id,:ppb,:part,:description,:unit,2,1000,2000)")->execute([':id'=>'QA-POI-'.$suffix,':ppb'=>$ppb,':part'=>$part['part_number'],':description'=>$part['part_name'],':unit'=>$part['unit_measure']]);
 $template=$db->prepare("INSERT INTO report_templates(template_key,code,title,version,schema_json,is_active) VALUES(:key,:code,:title,1,'{}',1)");
 $template->execute([':key'=>'qa-monitor-'.strtolower($suffix),':code'=>'MON-PROC',':title'=>'QA Monitoring']);$monitorTemplate=(int)$db->lastInsertId();
 $template->execute([':key'=>'qa-weekly-'.strtolower($suffix),':code'=>'RPW',':title'=>'QA Weekly']);$weeklyTemplate=(int)$db->lastInsertId();
 $insertReport=$db->prepare("INSERT INTO report_records(report_id,template_id,client_key,report_number,status,source_method,field_data,draft_data,standardized_payload,has_pending_attachments,created_by,finalized_at,final_number_key) VALUES(:id,:template,:client,:number,'FINAL','form','{}','{}','{}',0,1,NOW(),:final)");
 $insertReport->execute([':id'=>$monitorReport,':template'=>$monitorTemplate,':client'=>'qa-mon-'.$suffix,':number'=>'QA-MON-'.$suffix,':final'=>'monitor|'.strtolower($suffix)]);
 $rows=[['nomor_spb'=>$spb,'tanggal_spb'=>$date,'nomor_jo'=>$context['wo_id'],'id_unit'=>$context['asset_id'],'nama_spare_part'=>$part['part_name'],'part_number'=>$part['part_number'],'qty'=>'2','satuan'=>$part['unit_measure'],'status_pengadaan'=>'Dipesan','rtw_terdampak'=>'Tidak']];
 $result=ReportIntegration::applyFinal($db,'procurement-monitoring',$monitorReport,[],$rows,1);batch10Assert($result['itemCount']===1,'monitoring applies one procurement row');
 $requestStatus=$db->query("SELECT status FROM purchase_requests WHERE spb_id=".$db->quote($spb))->fetchColumn();$itemStatus=$db->query("SELECT status FROM purchase_request_items WHERE id=".$db->quote($requestItem))->fetchColumn();$orderStatus=$db->query("SELECT status FROM purchase_orders WHERE ppb_id=".$db->quote($ppb))->fetchColumn();
 batch10Assert($requestStatus==='Ordered'&&$itemStatus==='Dipesan'&&$orderStatus==='Ordered','monitoring updates SPB item and PPB status');
 batch10Assert(!empty(ReportIntegration::applyFinal($db,'procurement-monitoring',$monitorReport,[],$rows,1)['alreadyApplied']),'monitoring retry is idempotent');
 $void=ReportIntegration::reverseFinal($db,$monitorReport,1);batch10Assert($void['procurementMonitoringItemCount']===1,'monitoring void reverses the ledger');
 batch10Assert($db->query("SELECT status FROM purchase_requests WHERE spb_id=".$db->quote($spb))->fetchColumn()==='Submitted'&&$db->query("SELECT status FROM purchase_orders WHERE ppb_id=".$db->quote($ppb))->fetchColumn()==='Submitted','monitoring void restores unchanged statuses');

 $insertReport->execute([':id'=>$weeklyReport,':template'=>$weeklyTemplate,':client'=>'qa-week-'.$suffix,':number'=>'QA-WEEK-'.$suffix,':final'=>'weekly|'.strtolower($suffix)]);
 $stock=(int)$part['stock_qty'];$price=max(1,(float)$part['unit_cost']);$weeklyRows=[['nama'=>$part['part_name'],'satuan'=>$part['unit_measure'],'harga'=>(string)$price,'in_lalu'=>(string)$stock,'in_ini'=>'0','in_total'=>(string)$stock,'out_lalu'=>'0','out_ini'=>'0','out_total'=>'0','saldo'=>(string)$stock,'nilai_saldo'=>(string)($stock*$price),'keterangan'=>'QA snapshot']];
 $weekly=ReportIntegration::applyFinal($db,'parts-weekly',$weeklyReport,['yard'=>'QA Yard','jenis_parts'=>$part['part_name'],'pekan'=>'40','tahun'=>'2026','tanggal'=>$date],$weeklyRows,1);batch10Assert($weekly['itemCount']===1,'weekly report creates one stock snapshot');
 $snapshot=$db->query("SELECT * FROM parts_weekly_snapshots WHERE report_id=".$db->quote($weeklyReport))->fetch();batch10Assert($snapshot&&(int)$snapshot['balance_variance']===0,'weekly snapshot compares reported and actual balance');
 batch10Assert((int)$db->query('SELECT stock_qty FROM parts WHERE part_id='.(int)$part['part_id'])->fetchColumn()===$stock,'weekly report never mutates actual stock');
 batch10Assert(!empty(ReportIntegration::applyFinal($db,'parts-weekly',$weeklyReport,['yard'=>'QA Yard','pekan'=>'40','tahun'=>'2026','tanggal'=>$date],$weeklyRows,1)['alreadyApplied']),'weekly retry is idempotent');
 $weeklyVoid=ReportIntegration::reverseFinal($db,$weeklyReport,1);batch10Assert($weeklyVoid['partsWeeklyItemCount']===1,'weekly void deactivates the snapshot');
 echo "\nBatch 10 integration tests: {$passed} passed.\n";
}finally{if($db->inTransaction())$db->rollBack();}
