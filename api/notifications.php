<?php
/**
 * System Notifications API Endpoint for ServicePlan-BRA
 * Handles fetching, creating, and marking notifications as read in MySQL database
 */

require_once 'db.php';
$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

// Ensure system_notifications table exists
try {
    $db->exec("CREATE TABLE IF NOT EXISTS `system_notifications` (
      `notification_id` int NOT NULL AUTO_INCREMENT,
      `menu_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
      `user_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
      `user_role` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'User',
      `action_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
      `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
      `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
      `involved_parties_json` json DEFAULT NULL,
      `related_tables_json` json DEFAULT NULL,
      `is_read` tinyint(1) DEFAULT '0',
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`notification_id`),
      KEY `idx_created_at` (`created_at`),
      KEY `idx_is_read` (`is_read`),
      KEY `idx_menu_name` (`menu_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) {
    // Ignore error if table exists
}

switch ($method) {
    case 'GET':
        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $stmt = $db->prepare("SELECT * FROM system_notifications ORDER BY created_at DESC LIMIT :limit");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $results = array_map(function($row) {
                return [
                    'id' => (int)$row['notification_id'],
                    'menu' => $row['menu_name'],
                    'badgeClass' => strtolower(explode(' ', trim($row['menu_name']))[0] ?? 'wo'),
                    'user' => $row['user_name'],
                    'role' => $row['user_role'],
                    'actionType' => $row['action_type'],
                    'title' => $row['title'],
                    'message' => $row['message'],
                    'involvedParties' => json_decode($row['involved_parties_json'] ?? '[]', true) ?: [],
                    'relatedTables' => json_decode($row['related_tables_json'] ?? '{}', true) ?: new stdClass(),
                    'isRead' => (bool)$row['is_read'],
                    'timestamp' => date('H:i \W\I\B (d M Y)', strtotime($row['created_at']))
                ];
            }, $rows);

            echo json_encode(["status" => "success", "data" => $results]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            echo json_encode(["status" => "error", "message" => "No notification data provided"]);
            break;
        }

        $action = $input['action'] ?? 'CREATE';

        if ($action === 'MARK_READ') {
            $id = (int)($input['notification_id'] ?? $input['id'] ?? 0);
            if ($id > 0) {
                $stmt = $db->prepare("UPDATE system_notifications SET is_read = 1 WHERE notification_id = :id");
                $stmt->execute([':id' => $id]);
            }
            echo json_encode(["status" => "success", "message" => "Notification marked as read"]);
            break;
        }

        if ($action === 'MARK_ALL_READ') {
            $db->exec("UPDATE system_notifications SET is_read = 1");
            echo json_encode(["status" => "success", "message" => "All notifications marked as read"]);
            break;
        }

        // CREATE
        try {
            $stmt = $db->prepare("INSERT INTO system_notifications 
                (menu_name, user_name, user_role, action_type, title, message, involved_parties_json, related_tables_json, is_read, created_at) 
                VALUES (:menu, :user, :role, :type, :title, :msg, :parties, :tables, :is_read, NOW())");

            $menu = $input['menu_name'] ?? $input['menu'] ?? 'System';
            $user = $input['user_name'] ?? $input['user'] ?? 'System User';
            $role = $input['user_role'] ?? $input['role'] ?? 'User';
            $type = $input['action_type'] ?? 'UPDATE';
            $title = $input['title'] ?? 'Pembaruan Aktivitas Sistem';
            $msg = $input['message'] ?? 'Pembaruan transaksi baru.';
            $parties = json_encode($input['involved_parties_json'] ?? $input['involvedParties'] ?? []);
            $tables = json_encode($input['related_tables_json'] ?? $input['relatedTables'] ?? new stdClass());
            $isRead = !empty($input['is_read']) ? 1 : 0;

            $stmt->execute([
                ':menu' => $menu,
                ':user' => $user,
                ':role' => $role,
                ':type' => $type,
                ':title' => $title,
                ':msg' => $msg,
                ':parties' => $parties,
                ':tables' => $tables,
                ':is_read' => $isRead
            ]);

            $newId = (int)$db->lastInsertId();
            echo json_encode(["status" => "success", "message" => "Notification created", "notification_id" => $newId]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method not allowed"]);
        break;
}
?>
