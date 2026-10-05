<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/ReportIntegration.php';

$db = Database::getInstance();
ReportIntegration::ensureTables($db);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_json_response(405, [
        'status' => 'error',
        'message' => 'Metode tidak diizinkan.',
    ]);
}

try {
    $sql = "SELECT cr.calibration_id, cr.report_id, cr.period_month, cr.report_date,
                   cr.instrument_name, cr.identification_no, cr.brand_type,
                   cr.planned_date, cr.performed_date, cr.execution_type,
                   cr.calibration_result, cr.follow_up_status, cr.notes,
                   l.location_name,
                   prepared.full_name AS prepared_by_name,
                   checked.full_name AS checked_by_name,
                   r.report_number
            FROM calibration_records cr
            INNER JOIN locations l ON l.location_id = cr.location_id
            INNER JOIN users prepared ON prepared.user_id = cr.prepared_by
            INNER JOIN users checked ON checked.user_id = cr.checked_by
            INNER JOIN report_records r ON r.report_id = cr.report_id
            WHERE cr.reversed_at IS NULL AND r.status = 'FINAL'";
    $params = [];
    if (!api_has_global_location_scope()) {
        $locationId = api_current_location_id();
        if ($locationId === null) {
            $sql .= ' AND 1 = 0';
        } else {
            $sql .= ' AND cr.location_id = :location_id';
            $params[':location_id'] = $locationId;
        }
    }
    $sql .= ' ORDER BY cr.planned_date ASC, cr.identification_no ASC, cr.calibration_id DESC LIMIT 500';
    $statement = $db->prepare($sql);
    $statement->execute($params);

    $records = array_map(static fn(array $row): array => [
        'id' => (int) $row['calibration_id'],
        'reportId' => (string) $row['report_id'],
        'reportNumber' => (string) $row['report_number'],
        'period' => (string) $row['period_month'],
        'reportDate' => (string) $row['report_date'],
        'instrumentName' => (string) $row['instrument_name'],
        'identification' => (string) $row['identification_no'],
        'brandType' => (string) $row['brand_type'],
        'plannedDate' => (string) $row['planned_date'],
        'performedDate' => (string) $row['performed_date'],
        'executionType' => (string) $row['execution_type'],
        'result' => (string) $row['calibration_result'],
        'followUp' => (string) $row['follow_up_status'],
        'notes' => (string) ($row['notes'] ?? ''),
        'location' => (string) $row['location_name'],
        'preparedBy' => (string) $row['prepared_by_name'],
        'checkedBy' => (string) $row['checked_by_name'],
    ], $statement->fetchAll(PDO::FETCH_ASSOC));

    api_json_response(200, [
        'status' => 'success',
        'data' => $records,
    ]);
} catch (Throwable $error) {
    error_log('Calibration API error: ' . $error->getMessage());
    api_json_response(500, [
        'status' => 'error',
        'message' => 'Register kalibrasi tidak dapat dimuat.',
    ]);
}
