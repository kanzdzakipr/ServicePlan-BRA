<?php
declare(strict_types=1);

require_once 'db.php';
require_once dirname(__DIR__) . '/core/ConditionMonitoring.php';

$db = Database::getInstance();
ConditionMonitoring::ensureTables($db);
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

function conditionRows(PDO $db, string $sql, string $scopePrefix): array
{
    $scope = api_location_scope_clause('a', $scopePrefix);
    if ($scope['sql'] !== '') $sql .= ' AND ' . $scope['sql'];
    $statement = $db->prepare($sql);
    $statement->execute($scope['params']);
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function conditionStatusFromColor(string $color): string
{
    return match ($color) {
        'RED' => 'danger',
        'YELLOW' => 'warning',
        default => 'success',
    };
}

try {
    if ($method === 'GET') {
        $tireRows = conditionRows(
            $db,
            "SELECT t.*, inspector.full_name AS inspector_name
             FROM tire_inspections t
             INNER JOIN assets a ON a.asset_id = t.asset_id
             LEFT JOIN users inspector ON inspector.user_id = t.inspected_by
             WHERE 1 = 1",
            'condition_tire_location_id'
        );
        usort($tireRows, static fn(array $left, array $right): int =>
            strcmp((string) $right['inspected_at'], (string) $left['inspected_at'])
            ?: ((int) $right['tire_inspection_id'] <=> (int) $left['tire_inspection_id'])
        );
        $latestTires = [];
        $history = [];
        foreach ($tireRows as $row) {
            $status = $row['status_level'] ?: conditionStatusFromColor((string) $row['condition_color']);
            $physical = $row['physical_condition'] ?: ($status === 'danger' ? 'DG' : 'Baik');
            $wear = $row['wear_pattern'] ?: ($status === 'success' ? 'Merata' : 'Aus tidak rata');
            $action = $row['recommended_action'] ?: ($status === 'danger' ? 'Ganti segera' : ($status === 'warning' ? 'Rotasi' : 'Monitor'));
            $record = [
                'id' => 'CM-TIRE-' . $row['tire_inspection_id'],
                'recordId' => (int) $row['tire_inspection_id'],
                'assetId' => (string) $row['asset_id'],
                'position' => (string) $row['tire_position'],
                'serialNo' => (string) ($row['tire_serial_no'] ?? ''),
                'tread' => $row['tread_depth_mm'] !== null ? (float) $row['tread_depth_mm'] : null,
                'pressure' => (int) $row['air_pressure_psi'],
                'physical' => $physical,
                'wearPattern' => $wear,
                'action' => $action,
                'notes' => (string) ($row['notes'] ?? ''),
                'status' => $status,
                'inspector' => (string) ($row['inspector_name'] ?? ''),
                'inspectedAt' => (string) $row['inspected_at'],
            ];
            $key = mb_strtolower($record['assetId']) . '|' . $record['position'];
            if (!isset($latestTires[$key])) $latestTires[$key] = $record;
            if ($row['inspected_by'] !== null) {
                $record['domain'] = 'Ban';
                $record['summary'] = $record['position'] . ' '
                    . ($record['tread'] === null ? $record['physical'] : $record['tread'] . ' mm / ' . $record['pressure'] . ' PSI')
                    . ' · ' . $record['action'];
                $history[] = $record;
            }
        }

        $greaseRows = conditionRows(
            $db,
            "SELECT g.*, inspector.full_name AS inspector_name
             FROM grease_logs g
             INNER JOIN assets a ON a.asset_id = g.asset_id
             LEFT JOIN users inspector ON inspector.user_id = g.inspected_by
             WHERE 1 = 1",
            'condition_grease_location_id'
        );
        usort($greaseRows, static fn(array $left, array $right): int =>
            strcmp((string) $right['inspected_at'], (string) $left['inspected_at'])
            ?: ((int) $right['grease_log_id'] <=> (int) $left['grease_log_id'])
        );
        $latestGrease = [];
        foreach ($greaseRows as $row) {
            $points = json_decode((string) $row['lubrication_points'], true);
            $record = [
                'id' => 'CM-GREASE-' . $row['grease_log_id'],
                'recordId' => (int) $row['grease_log_id'],
                'assetId' => (string) $row['asset_id'],
                'currentHm' => (float) $row['current_hm'],
                'lastHm' => (float) $row['last_grease_hm'],
                'interval' => (float) $row['interval_hm'],
                'quantity' => (float) $row['quantity_kg'],
                'greaseType' => (string) $row['grease_type'],
                'points' => is_array($points) ? $points : [],
                'note' => (string) ($row['notes'] ?? ''),
                'status' => (string) $row['status_level'],
                'inspector' => (string) ($row['inspector_name'] ?? ''),
                'inspectedAt' => (string) $row['inspected_at'],
            ];
            $key = mb_strtolower($record['assetId']);
            if (!isset($latestGrease[$key])) $latestGrease[$key] = $record;
            $record['domain'] = 'Grease';
            $record['summary'] = ($record['currentHm'] - $record['lastHm']) . ' HM sejak grease · '
                . $record['quantity'] . ' kg ' . $record['greaseType'];
            $history[] = $record;
        }

        $batteryRows = conditionRows(
            $db,
            "SELECT b.*, inspector.full_name AS inspector_name
             FROM battery_logs b
             INNER JOIN assets a ON a.asset_id = b.asset_id
             LEFT JOIN users inspector ON inspector.user_id = b.inspected_by
             WHERE 1 = 1",
            'condition_battery_location_id'
        );
        usort($batteryRows, static fn(array $left, array $right): int =>
            strcmp((string) $right['inspected_at'], (string) $left['inspected_at'])
            ?: ((int) $right['battery_log_id'] <=> (int) $left['battery_log_id'])
        );
        $latestBatteries = [];
        foreach ($batteryRows as $row) {
            $status = $row['status_level'] ?: 'success';
            $record = [
                'id' => 'CM-BATTERY-' . $row['battery_log_id'],
                'recordId' => (int) $row['battery_log_id'],
                'assetId' => (string) $row['asset_id'],
                'brand' => (string) ($row['battery_brand'] ?? ''),
                'type' => (string) ($row['battery_type'] ?? ''),
                'voltage' => (float) $row['voltage_val'],
                'cca' => $row['cca_percent'] !== null ? (float) $row['cca_percent'] : 0,
                'installDate' => (string) ($row['installation_date'] ?? ''),
                'terminal' => (string) ($row['terminal_condition'] ?? 'Bersih & kencang'),
                'electrolyte' => (string) ($row['electrolyte_level'] ?? ($row['water_level'] === 'LOW' ? 'Rendah' : 'Normal')),
                'caseCondition' => (string) ($row['case_condition'] ?? 'Baik'),
                'note' => (string) ($row['notes'] ?? ''),
                'status' => $status,
                'inspector' => (string) ($row['inspector_name'] ?? ''),
                'inspectedAt' => (string) $row['inspected_at'],
            ];
            $key = mb_strtolower($record['assetId']);
            if (!isset($latestBatteries[$key])) $latestBatteries[$key] = $record;
            if ($row['inspected_by'] !== null) {
                $record['domain'] = 'Aki';
                $record['summary'] = $record['voltage'] . ' V · ' . $record['cca'] . '% CCA · ' . $record['terminal'];
                $history[] = $record;
            }
        }

        usort($history, static fn(array $left, array $right): int =>
            strcmp((string) $right['inspectedAt'], (string) $left['inspectedAt'])
        );
        api_json_response(200, [
            'status' => 'success',
            'data' => [
                'latest' => [
                    'tires' => array_values($latestTires),
                    'grease' => array_values($latestGrease),
                    'batteries' => array_values($latestBatteries),
                ],
                'history' => array_slice($history, 0, 500),
            ],
        ]);
    }

    if ($method === 'POST') {
        $data = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($data)) throw new DomainException('Payload Condition Monitoring tidak valid.');
        $domain = strtolower(trim((string) ($data['domain'] ?? '')));
        $assetId = trim((string) ($data['asset_id'] ?? ''));
        api_require_asset_access($db, $assetId);

        $db->beginTransaction();
        try {
            $record = ConditionMonitoring::save(
                $db,
                $domain,
                $data,
                (int) api_current_user()['id']
            );
            $db->commit();
        } catch (Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            throw $error;
        }
        api_json_response(201, [
            'status' => 'success',
            'message' => $record['domain'] . ' berhasil disimpan ke database.',
            'data' => $record,
        ]);
    }

    api_json_response(405, ['status' => 'error', 'message' => 'Method not allowed']);
} catch (DomainException $error) {
    api_json_response(422, ['status' => 'error', 'message' => $error->getMessage()]);
} catch (Throwable $error) {
    error_log('Condition Monitoring API failed: ' . $error->getMessage());
    api_json_response(500, ['status' => 'error', 'message' => 'Condition Monitoring gagal diproses.']);
}
