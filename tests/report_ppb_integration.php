<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/core/ReportIntegration.php';
$dsn=(string)(getenv('REPORT_TEST_DSN')?:'mysql:host=127.0.0.1;port=3306;dbname=u646470441_ServicePlanBRA;charset=utf8mb4');
try{$db=new PDO($dsn,(string)(getenv('REPORT_TEST_DB_USER')?:'root'),(string)(getenv('REPORT_TEST_DB_PASSWORD')?:''),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);}catch(Throwable $e){echo "SKIP: {$e->getMessage()}\n";exit(0);}
ReportIntegration::ensureTables($db); $passed=0;
function ppbAssert(bool $ok,string $message):void{global $passed;if(!$ok)throw new RuntimeException("FAIL: {$message}");$passed++;echo "PASS: {$message}\n";}
$context=$db->query("SELECT w.wo_id,w.asset_id FROM work_orders w JOIN assets a ON a.asset_id=w.asset_id WHERE a.is_active=1 AND w.status NOT IN ('Closed','Cancelled') ORDER BY w.wo_id LIMIT 1")->fetch();
$part=$db->query('SELECT part_number,part_name,unit_measure,unit_cost FROM parts ORDER BY part_id LIMIT 1')->fetch();
if(!$context||!$part){echo "SKIP: prerequisite data unavailable.\n";exit(0);}
$suffix=strtoupper(substr(bin2hex(random_bytes(8)),0,12));$spbId='QA-SPB-'.$suffix;$reportId='91919191-9191-4919-8919-'.$suffix;$report2='92929292-9292-4929-8929-'.$suffix;$date=(new DateTimeImmutable('today'))->format('Y-m-d');
$db->beginTransaction();
try{
 $db->prepare("INSERT INTO purchase_requests(spb_id,wo_id,asset_id,requested_by,urgency,status,requested_at) VALUES(:spb,:wo,:asset,1,'Normal','Submitted',NOW())")->execute([':spb'=>$spbId,':wo'=>$context['wo_id'],':asset'=>$context['asset_id']]);
 $db->prepare("INSERT INTO purchase_request_items(id,spb_id,part_number,description,qty_requested,status) VALUES(:id,:spb,:part,:description,2,'Menunggu Approval')")->execute([':id'=>'QA-PRI-'.$suffix,':spb'=>$spbId,':part'=>$part['part_number'],':description'=>$part['part_name']]);
 $db->prepare("INSERT INTO report_templates(template_key,code,title,version,schema_json,is_active) VALUES(:k,'P-3','QA PPB',1,'{}',1)")->execute([':k'=>'qa-ppb-'.strtolower($suffix)]);$template=(int)$db->lastInsertId();
 $insertReport=$db->prepare("INSERT INTO report_records(report_id,template_id,client_key,report_number,status,source_method,field_data,draft_data,standardized_payload,has_pending_attachments,created_by,finalized_at,final_number_key) VALUES(:id,:template,:client,:number,'FINAL','form','{}','{}','{}',0,1,NOW(),:final)");
 $makeReport=static function(string $id,string $number)use($insertReport,$template):void{$insertReport->execute([':id'=>$id,':template'=>$template,':client'=>'qa-client-'.strtolower($number),':number'=>$number,':final'=>'ppb|'.strtolower($number)]);};
 $fields=['nomor_ppb'=>'QA-PPB-'.$suffix,'nomor_spb'=>$spbId,'kepada'=>'PT QA Vendor','project'=>'QA Project','nomor_penawaran'=>'Q-'.$suffix,'tanggal_penawaran'=>$date,'batas_penyerahan'=>(new DateTimeImmutable('+7 days'))->format('Y-m-d'),'tempat_penyerahan'=>'Workshop QA'];
 $price=max(1000,(float)$part['unit_cost']);$rows=[['nama'=>$part['part_name'],'sc'=>$part['part_number'],'satuan'=>$part['unit_measure'],'jumlah'=>'2','harga'=>(string)$price,'total'=>(string)($price*2),'keterangan'=>'QA']];
 $before=(int)$db->query('SELECT COUNT(*) FROM purchase_orders')->fetchColumn();$makeReport($reportId,$fields['nomor_ppb']);
 $result=ReportIntegration::applyFinal($db,'ppb',$reportId,$fields,$rows,1);
 ppbAssert($result['applied']&&$result['itemCount']===1,'final PPB creates one order item');
 $order=$db->prepare('SELECT * FROM purchase_orders WHERE ppb_id=:id');$order->execute([':id'=>$fields['nomor_ppb']]);$created=$order->fetch();
 ppbAssert($created&&$created['spb_id']===$spbId&&$created['asset_id']===$context['asset_id'],'PPB links the source SPB and asset');
 ppbAssert(abs((float)$created['tax_amount']-($price*2*0.11))<0.01,'PPN 11 percent is calculated correctly');
 $item=$db->prepare('SELECT * FROM purchase_order_items WHERE ppb_id=:id');$item->execute([':id'=>$fields['nomor_ppb']]);$line=$item->fetch();
 ppbAssert($line&&$line['part_number']===$part['part_number']&&(int)$line['quantity']===2,'part and quantity are mapped');
 $retry=ReportIntegration::applyFinal($db,'ppb',$reportId,$fields,$rows,1);ppbAssert(!empty($retry['alreadyApplied']),'retry is idempotent');
 ppbAssert((int)$db->query('SELECT COUNT(*) FROM purchase_orders')->fetchColumn()===$before+1,'retry does not duplicate PPB');
 $void=ReportIntegration::reverseFinal($db,$reportId,1);$order->execute([':id'=>$fields['nomor_ppb']]);ppbAssert($void['purchaseOrderDeletedCount']===1&&!$order->fetch(),'void removes unchanged PPB');
 ppbAssert(ReportIntegration::reverseFinal($db,$reportId,1)['applied']===false,'repeated void is idempotent');
 $fields2=[...$fields,'nomor_ppb'=>'QA-PPB-P-'.$suffix];$makeReport($report2,$fields2['nomor_ppb']);ReportIntegration::applyFinal($db,'ppb',$report2,$fields2,$rows,1);
 $db->prepare("UPDATE purchase_orders SET status='Ordered' WHERE ppb_id=:id")->execute([':id'=>$fields2['nomor_ppb']]);$void2=ReportIntegration::reverseFinal($db,$report2,1);$order->execute([':id'=>$fields2['nomor_ppb']]);ppbAssert($void2['purchaseOrderPreservedCount']===1&&(bool)$order->fetch(),'void preserves processed PPB');
 $badRows=$rows;$badRows[0]['total']='1';try{ReportIntegration::applyFinal($db,'ppb','94949494-9494-4949-8949-'.$suffix,[...$fields,'nomor_ppb'=>'QA-BAD2-'.$suffix],$badRows,1);ppbAssert(false,'invalid line total must be rejected');}catch(DomainException $e){ppbAssert(str_contains($e->getMessage(),'jumlah harga'),'invalid line total is rejected');}
 echo "\nPPB integration tests: {$passed} passed.\n";
}finally{if($db->inTransaction())$db->rollBack();}
