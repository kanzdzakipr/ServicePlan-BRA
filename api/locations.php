<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once dirname(__DIR__) . '/core/AuthMiddleware.php';
require_once __DIR__ . '/security.php';

$db = Database::getInstance();
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

api_require_authenticated();
$currentUser = api_current_user();
$roles = (array) ($currentUser['roles'] ?? []);

const ALLOWED_LOCATION_TYPES = ['Yard', 'Pit', 'Borrow Pit', 'Workshop', 'Branch', 'Site Area'];

function can_manage_master_locations(array $roles, array $user): bool
{
    return api_can_manage_users($user)
        || in_array('Administrator', $roles, true)
        || in_array('Asset Manager', $roles, true)
        || in_array('Equipment Manager', $roles, true)
        || api_has_permission('locations.manage');
}

switch ($method) {
    case 'GET':
        $activeOnly = isset($_GET['active_only']) && (string) $_GET['active_only'] === '1';
        $locationSql = "
            SELECT l.location_id, l.location_name, l.location_type, l.region,
                   l.latitude, l.longitude, l.is_active, l.created_at,
                   COUNT(a.asset_id) AS unit_count
            FROM locations l
            LEFT JOIN assets a ON a.current_location_id = l.location_id
        ";
        $whereClauses = [];
        $params = [];

        if ($activeOnly) {
            $whereClauses[] = 'l.is_active = 1';
        }

        if (!api_has_global_location_scope() && !can_manage_master_locations($roles, $currentUser)) {
            $assignedLoc = api_current_location_id();
            if ($assignedLoc !== null) {
                // If reference mode is not explicitly requested, scope down
                if (!isset($_GET['for_reference'])) {
                    $whereClauses[] = 'l.location_id = :assigned_loc';
                    $params[':assigned_loc'] = $assignedLoc;
                }
            }
        }

        if (!empty($whereClauses)) {
            $locationSql .= ' WHERE ' . implode(' AND ', $whereClauses);
        }

        $locationSql .= ' GROUP BY l.location_id ORDER BY l.location_name ASC';

        try {
            $stmt = $db->prepare($locationSql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format coordinates and counts
            $data = array_map(static function (array $row): array {
                return [
                    'location_id' => (int) $row['location_id'],
                    'location_name' => (string) $row['location_name'],
                    'location_type' => (string) ($row['location_type'] ?? 'Site Area'),
                    'region' => (string) ($row['region'] ?? 'Riau / Pekanbaru'),
                    'latitude' => $row['latitude'] !== null ? (float) $row['latitude'] : null,
                    'longitude' => $row['longitude'] !== null ? (float) $row['longitude'] : null,
                    'is_active' => (int) ($row['is_active'] ?? 1) === 1,
                    'created_at' => $row['created_at'],
                    'unit_count' => (int) ($row['unit_count'] ?? 0),
                ];
            }, $rows);

            api_json_response(200, [
                'status' => 'success',
                'data' => $data,
                'can_manage' => can_manage_master_locations($roles, $currentUser),
            ]);
        } catch (PDOException $e) {
            error_log('Failed to fetch locations: ' . $e->getMessage());
            api_json_response(500, ['status' => 'error', 'message' => 'Gagal memuat master lokasi: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        if (!can_manage_master_locations($roles, $currentUser)) {
            api_json_response(403, [
                'status' => 'error',
                'code' => 'FORBIDDEN',
                'message' => 'Hanya Administrator, Asset Manager, atau Equipment Manager yang dapat menambah master lokasi.',
            ]);
        }

        $input = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }

        $name = trim((string) ($input['location_name'] ?? ''));
        $type = trim((string) ($input['location_type'] ?? 'Site Area'));
        $region = trim((string) ($input['region'] ?? 'Riau / Pekanbaru'));
        $lat = isset($input['latitude']) && $input['latitude'] !== '' ? (float) $input['latitude'] : null;
        $lng = isset($input['longitude']) && $input['longitude'] !== '' ? (float) $input['longitude'] : null;
        $isActive = isset($input['is_active']) ? ((int) $input['is_active'] === 1 ? 1 : 0) : 1;

        if ($name === '' || mb_strlen($name, 'UTF-8') < 3 || mb_strlen($name, 'UTF-8') > 100) {
            api_json_response(422, [
                'status' => 'error',
                'code' => 'INVALID_LOCATION_NAME',
                'message' => 'Nama lokasi wajib diisi antara 3 hingga 100 karakter.',
            ]);
        }

        if (!in_array($type, ALLOWED_LOCATION_TYPES, true)) {
            $type = 'Site Area';
        }

        if (mb_strlen($region, 'UTF-8') > 50) {
            $region = mb_substr($region, 0, 50, 'UTF-8');
        }

        // Check duplicate name
        $check = $db->prepare('SELECT location_id FROM locations WHERE LOWER(location_name) = LOWER(:name) LIMIT 1');
        $check->execute([':name' => $name]);
        if ($check->fetch()) {
            api_json_response(409, [
                'status' => 'error',
                'code' => 'DUPLICATE_LOCATION',
                'message' => "Lokasi dengan nama '{$name}' sudah ada.",
            ]);
        }

        try {
            $stmt = $db->prepare('
                INSERT INTO locations (location_name, location_type, region, latitude, longitude, is_active)
                VALUES (:name, :type, :region, :lat, :lng, :is_active)
            ');
            $stmt->execute([
                ':name' => $name,
                ':type' => $type,
                ':region' => $region !== '' ? $region : 'Riau / Pekanbaru',
                ':lat' => $lat,
                ':lng' => $lng,
                ':is_active' => $isActive,
            ]);
            $newId = (int) $db->lastInsertId();

            api_json_response(201, [
                'status' => 'success',
                'message' => "Master lokasi '{$name}' berhasil ditambahkan.",
                'data' => [
                    'location_id' => $newId,
                    'location_name' => $name,
                    'location_type' => $type,
                    'region' => $region,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'is_active' => $isActive === 1,
                    'unit_count' => 0,
                ],
            ]);
        } catch (PDOException $e) {
            error_log('Failed to create location: ' . $e->getMessage());
            api_json_response(500, ['status' => 'error', 'message' => 'Gagal menyimpan lokasi baru: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        if (!can_manage_master_locations($roles, $currentUser)) {
            api_json_response(403, [
                'status' => 'error',
                'code' => 'FORBIDDEN',
                'message' => 'Hanya Administrator atau Asset Manager yang berwenang memperbarui master lokasi.',
            ]);
        }

        $input = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($input)) {
            api_json_response(422, ['status' => 'error', 'message' => 'Payload JSON tidak valid.']);
        }

        $locationId = (int) ($input['location_id'] ?? $_GET['id'] ?? 0);
        if ($locationId <= 0) {
            api_json_response(422, ['status' => 'error', 'message' => 'ID lokasi wajib disertakan.']);
        }

        // Verify location exists
        $currStmt = $db->prepare('SELECT * FROM locations WHERE location_id = :id');
        $currStmt->execute([':id' => $locationId]);
        $existing = $currStmt->fetch(PDO::FETCH_ASSOC);
        if (!$existing) {
            api_json_response(404, ['status' => 'error', 'message' => 'Lokasi tidak ditemukan.']);
        }

        $name = isset($input['location_name']) ? trim((string) $input['location_name']) : (string) $existing['location_name'];
        $type = isset($input['location_type']) ? trim((string) $input['location_type']) : (string) $existing['location_type'];
        $region = isset($input['region']) ? trim((string) $input['region']) : (string) $existing['region'];
        $lat = array_key_exists('latitude', $input) ? ($input['latitude'] !== null && $input['latitude'] !== '' ? (float) $input['latitude'] : null) : ($existing['latitude'] !== null ? (float) $existing['latitude'] : null);
        $lng = array_key_exists('longitude', $input) ? ($input['longitude'] !== null && $input['longitude'] !== '' ? (float) $input['longitude'] : null) : ($existing['longitude'] !== null ? (float) $existing['longitude'] : null);
        $isActive = array_key_exists('is_active', $input) ? ((int) $input['is_active'] === 1 ? 1 : 0) : (int) $existing['is_active'];

        if ($name === '' || mb_strlen($name, 'UTF-8') < 3 || mb_strlen($name, 'UTF-8') > 100) {
            api_json_response(422, ['status' => 'error', 'message' => 'Nama lokasi tidak valid (3-100 karakter).']);
        }

        if (!in_array($type, ALLOWED_LOCATION_TYPES, true)) {
            $type = (string) $existing['location_type'];
        }

        // Check duplicate name against other locations
        $check = $db->prepare('SELECT location_id FROM locations WHERE LOWER(location_name) = LOWER(:name) AND location_id != :id LIMIT 1');
        $check->execute([':name' => $name, ':id' => $locationId]);
        if ($check->fetch()) {
            api_json_response(409, [
                'status' => 'error',
                'code' => 'DUPLICATE_LOCATION',
                'message' => "Nama lokasi '{$name}' sudah digunakan oleh lokasi lain.",
            ]);
        }

        try {
            $upd = $db->prepare('
                UPDATE locations
                SET location_name = :name, location_type = :type, region = :region,
                    latitude = :lat, longitude = :lng, is_active = :is_active
                WHERE location_id = :id
            ');
            $upd->execute([
                ':name' => $name,
                ':type' => $type,
                ':region' => $region,
                ':lat' => $lat,
                ':lng' => $lng,
                ':is_active' => $isActive,
                ':id' => $locationId,
            ]);

            api_json_response(200, [
                'status' => 'success',
                'message' => "Master lokasi '{$name}' berhasil diperbarui.",
                'data' => [
                    'location_id' => $locationId,
                    'location_name' => $name,
                    'location_type' => $type,
                    'region' => $region,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'is_active' => $isActive === 1,
                ],
            ]);
        } catch (PDOException $e) {
            error_log('Failed to update location: ' . $e->getMessage());
            api_json_response(500, ['status' => 'error', 'message' => 'Gagal memperbarui master lokasi: ' . $e->getMessage()]);
        }
        break;

    default:
        api_json_response(405, ['status' => 'error', 'message' => 'Metode HTTP tidak didukung.']);
}
