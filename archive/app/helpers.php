<?php
function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): never { header('Location: ' . $url); exit; }
function is_logged_in(): bool { return !empty($_SESSION['user']); }
function require_login(): void { if (!is_logged_in()) redirect('login.php'); }
function current_user(): array { return $_SESSION['user'] ?? []; }
function current_role(): string { return $_SESSION['user']['role'] ?? 'guest'; }
function role_label(string $role): string { return ['asset_manager'=>'Asset Manager','head_equipment'=>'Head of Equipment','planner'=>'Planner','mechanic'=>'Mekanik','logistics'=>'Logistik','viewer'=>'Viewer','admin'=>'Asset Manager'][$role] ?? ucwords(str_replace('_',' ',$role)); }
function permissions(): array {
 return [
  'asset_manager'=>['*'],
  'head_equipment'=>['dashboard','units.view','units.edit','service.view','service.edit','maintenance.view','maintenance.edit','logistics.view','logistics.approve','documents.view','documents.upload','inspections.view','analytics.view','reports.export','qr.view','audit.view','history.view','notifications.view'],
  'planner'=>['dashboard','units.view','units.edit','service.view','service.edit','maintenance.view','maintenance.edit','logistics.view','logistics.request','documents.view','documents.upload','inspections.view','inspections.edit','analytics.view','reports.export','qr.view','history.view','notifications.view'],
  'mechanic'=>['dashboard','units.view','service.view','maintenance.view','maintenance.edit','documents.view','documents.upload','inspections.view','inspections.edit','history.view','notifications.view','qr.view'],
  'logistics'=>['dashboard','units.view','logistics.view','logistics.request','logistics.edit','documents.view','documents.upload','notifications.view','reports.export'],
  'viewer'=>['dashboard','units.view','service.view','maintenance.view','logistics.view','documents.view','inspections.view','analytics.view','reports.export','qr.view','history.view','notifications.view'],
 ];
}
function can(string $permission): bool { $p=permissions()[current_role()] ?? []; return in_array('*',$p,true)||in_array($permission,$p,true); }
function require_permission(string $permission): void { if(!can($permission)){ http_response_code(403); exit('Akses ditolak untuk jabatan Anda.'); } }
function flash(string $key, ?string $value = null): ?string { if ($value !== null) { $_SESSION['_flash'][$key] = $value; return null; } $msg = $_SESSION['_flash'][$key] ?? null; unset($_SESSION['_flash'][$key]); return $msg; }
function csrf_token(): string { if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32)); return $_SESSION['_csrf']; }
function verify_csrf(): void { if (!hash_equals($_SESSION['_csrf'] ?? '', $_POST['_csrf'] ?? '')) { http_response_code(419); exit('Token CSRF tidak valid.'); } }
function rupiah($n): string { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function page_url(string $page, array $params=[]): string { return 'dashboard.php?' . http_build_query(array_merge(['page'=>$page],$params)); }
function active_page(string $name, string $current): string { return $name === $current ? 'active' : ''; }
function status_class(string $status): string { $s=strtolower($status); if(str_contains($s,'selesai')||str_contains($s,'ready')||str_contains($s,'aman')||str_contains($s,'aktif')||str_contains($s,'disetujui'))return 'st-green'; if(str_contains($s,'terlambat')||str_contains($s,'breakdown')||str_contains($s,'tertunda')||str_contains($s,'ditolak'))return 'st-red'; if(str_contains($s,'jatuh')||str_contains($s,'menunggu')||str_contains($s,'downtime')||str_contains($s,'draft'))return 'st-orange'; return 'st-blue'; }
function audit(PDO $pdo,string $action,?string $entityType=null,?int $entityId=null,string $description=''): void { $stmt=$pdo->prepare('INSERT INTO audit_logs(user_id,action,entity_type,entity_id,description,ip_address,user_agent) VALUES(?,?,?,?,?,?,?)'); $stmt->execute([current_user()['id']??null,$action,$entityType,$entityId,$description,$_SERVER['REMOTE_ADDR']??null,substr($_SERVER['HTTP_USER_AGENT']??'',0,255)]); }
function add_history(PDO $pdo,string $type,int $id,?string $old,string $new,string $notes=''): void { $stmt=$pdo->prepare('INSERT INTO status_histories(entity_type,entity_id,old_status,new_status,notes,changed_by) VALUES(?,?,?,?,?,?)'); $stmt->execute([$type,$id,$old,$new,$notes,current_user()['id']]); }
function notify(PDO $pdo,?int $userId,string $type,string $title,string $message,string $url=''): void { $stmt=$pdo->prepare('INSERT INTO notifications(user_id,type,title,message,target_url) VALUES(?,?,?,?,?)'); $stmt->execute([$userId,$type,$title,$message,$url]); }
function generate_number(PDO $pdo,string $prefix,string $table,string $column): string { $date=date('ymd'); $like=$prefix.'-'.$date.'-%'; $stmt=$pdo->prepare("SELECT $column FROM $table WHERE $column LIKE ? ORDER BY id DESC LIMIT 1"); $stmt->execute([$like]); $last=$stmt->fetchColumn(); $seq=$last?(int)substr($last,-4)+1:1; return sprintf('%s-%s-%04d',$prefix,$date,$seq); }
function upload_file(array $file,string $folder,array $allowed=['image/jpeg','image/png','image/webp','application/pdf']): array { if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) throw new RuntimeException('File gagal diunggah.'); if(($file['size']??0)>10*1024*1024) throw new RuntimeException('Ukuran file maksimum 10 MB.'); $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']); if(!in_array($mime,$allowed,true)) throw new RuntimeException('Format file tidak diizinkan.'); $ext=pathinfo($file['name'],PATHINFO_EXTENSION); $stored=bin2hex(random_bytes(16)).'.'.strtolower($ext); $base=__DIR__.'/../uploads/'.$folder; if(!is_dir($base))mkdir($base,0775,true); $dest=$base.'/'.$stored; if(!move_uploaded_file($file['tmp_name'],$dest))throw new RuntimeException('Tidak dapat menyimpan file.'); return ['original_name'=>$file['name'],'stored_name'=>$stored,'file_path'=>'uploads/'.$folder.'/'.$stored,'mime_type'=>$mime,'file_size'=>$file['size']]; }
function service_status(float $current,float $next,string $date): string { if($current>$next||$date<date('Y-m-d'))return 'Terlambat'; if($current==$next||$date===date('Y-m-d'))return 'Jatuh Tempo'; if(($next-$current)<=50||$date<=date('Y-m-d',strtotime('+7 days')))return 'Akan Service'; return 'Terjadwal'; }
