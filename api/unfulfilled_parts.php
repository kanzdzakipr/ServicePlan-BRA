<?php
declare(strict_types=1);

require_once 'db.php';
$db = Database::getInstance();
api_require_permission('logistics.read');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    api_json_response(405, ['status'=>'error','message'=>'Metode tidak didukung.']);
}

$scope = api_location_scope_clause('a', 'unfulfilled_location');
$where = ["pr.status NOT IN ('Cancelled','Closed')", 'COALESCE(pri.base_quantity, pri.qty_requested) > COALESCE(pri.fulfilled_quantity,0)'];
$params = $scope['params'];
if ($scope['sql'] !== '') $where[] = $scope['sql'];
$status = trim((string)($_GET['status'] ?? ''));
if ($status !== '') { $where[] = 'pri.status = :status'; $params[':status'] = $status; }

$sql = "SELECT pri.id, pri.spb_id, pr.wo_id, pr.asset_id, a.asset_code,
               COALESCE(p.part_number,pri.part_number) AS sku,
               COALESCE(p.technical_name,p.part_name,pri.description) AS technical_name,
               pri.description, pri.qty_requested, pri.transaction_uom,
               COALESCE(pri.base_quantity,pri.qty_requested) AS base_quantity,
               COALESCE(pri.fulfilled_quantity,0) AS fulfilled_quantity,
               COALESCE(p.base_uom,p.unit_measure,pri.transaction_uom,'Pcs') AS base_uom,
               pri.status, pr.requested_at, pr.requested_by, requester.full_name AS requester_name,
               pri.fulfilment_pic_user_id, pic.full_name AS fulfilment_pic_name,
               DATEDIFF(CURDATE(),DATE(pr.requested_at)) AS aging_calendar_days,
               l.location_name
        FROM purchase_request_items pri
        INNER JOIN purchase_requests pr ON pr.spb_id=pri.spb_id
        LEFT JOIN parts p ON p.part_id=pri.part_id OR (pri.part_id IS NULL AND p.part_number=pri.part_number)
        LEFT JOIN assets a ON a.asset_id=pr.asset_id
        LEFT JOIN locations l ON l.location_id=a.current_location_id
        LEFT JOIN users requester ON requester.user_id=pr.requested_by
        LEFT JOIN users pic ON pic.user_id=pri.fulfilment_pic_user_id
        WHERE " . implode(' AND ', $where) . ' ORDER BY pr.requested_at ASC,pri.id ASC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as &$row) {
    $start = new DateTimeImmutable(substr((string)$row['requested_at'], 0, 10));
    $end = new DateTimeImmutable('today');
    $working = 0;
    for ($date=$start; $date <= $end; $date=$date->modify('+1 day')) {
        if ((int)$date->format('N') <= 5) $working++;
    }
    $row['aging_working_days'] = max(0, $working - 1);
    $row['outstanding_base_quantity'] = max(0, (float)$row['base_quantity'] - (float)$row['fulfilled_quantity']);
}
unset($row);

api_json_response(200, ['status'=>'success','data'=>$rows,'summary'=>[
    'item_count'=>count($rows),
    'oldest_calendar_days'=>$rows ? max(array_column($rows,'aging_calendar_days')) : null,
    'definition'=>'Aging kalender dan hari kerja Senin-Jumat; hari libur belum dikurangkan.',
]]);
