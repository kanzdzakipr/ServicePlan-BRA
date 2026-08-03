<?php
require_once 'db.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

// Endpoint ini bisa dikembangkan lebih lanjut untuk menghandle data logistik
// seperti parts, fuel logs, costs, dsb.
// Saat ini hanya implementasi basic GET untuk data master parts

switch ($method) {
    case 'GET':
        if (isset($_GET['type'])) {
            if ($_GET['type'] == 'parts') {
                $stmt = $db->query("SELECT * FROM parts");
                echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
                break;
            } elseif ($_GET['type'] == 'costs') {
                $stmt = $db->query("SELECT * FROM cost_financial_monthly");
                echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
                break;
            }
        }
        echo json_encode(["status" => "error", "message" => "Type not specified or supported"]);
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method not allowed"]);
        break;
}
?>
