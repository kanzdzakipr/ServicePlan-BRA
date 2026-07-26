<?php
/**
 * CLI Data Seeder & Migration Script (ServicePlan-BRA)
 * Converts legacy data from data.json into MySQL relational tables.
 */

class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $port = getenv('DB_PORT') ?: '3306';
            $db   = getenv('DB_NAME') ?: 'serviceplan_bra';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') ?: '';
            $charset = 'utf8mb4';

            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                die("Database Connection Error: " . $e->getMessage() . "\n");
            }
        }
        return self::$instance;
    }
}

class SeederDataJson {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function run(): void {
        echo "Starting Migration from data.json...\n";
        
        $jsonFile = __DIR__ . '/../data.json';
        if (!file_exists($jsonFile)) {
            die("Error: data.json not found at {$jsonFile}\n");
        }

        $data = json_decode(file_get_contents($jsonFile), true);
        if (!$data) {
            die("Error: Invalid JSON format\n");
        }

        $this->db->beginTransaction();

        try {
            $this->seedLocationsAndAssets($data['assets'] ?? []);
            $this->seedWorkOrders($data['work_orders'] ?? []);
            $this->seedCosts($data['costs'] ?? []);

            $this->db->commit();
            echo "Migration Completed Successfully!\n";
        } catch (Exception $e) {
            $this->db->rollBack();
            echo "Migration Failed: " . $e->getMessage() . "\n";
        }
    }

    private function seedLocationsAndAssets(array $assets): void {
        $stmtLoc = $this->db->prepare("INSERT IGNORE INTO locations (location_name) VALUES (:name)");
        $stmtLocGet = $this->db->prepare("SELECT location_id FROM locations WHERE location_name = :name");
        
        $stmtAsset = $this->db->prepare("
            INSERT INTO assets (asset_id, asset_code, serial_number, license_plate, type, category, status, current_location_id, raw_location_notes)
            VALUES (:asset_id, :code, :sn, :plate, :type, :cat, :status, :loc_id, :raw_loc)
            ON DUPLICATE KEY UPDATE 
                status = VALUES(status), 
                current_location_id = VALUES(current_location_id),
                raw_location_notes = VALUES(raw_location_notes)
        ");

        foreach ($assets as $item) {
            $rawId = trim($item['id']);
            $rawLoc = trim($item['location']);

            // Parse ID (Extract Code, SN, Plate)
            $code = $rawId;
            $sn = null;
            $plate = null;

            if (preg_match('/^([A-Z0-9\-\.]+)/i', $rawId, $m)) {
                $code = $m[1];
            }
            if (preg_match('/SN[:\s]+([A-Z0-9]+)/i', $rawId, $m)) {
                $sn = $m[1];
            }
            if (preg_match('/([A-Z]{1,2}\s+\d{1,4}\s+[A-Z]{1,3})/i', $rawId, $m)) {
                $plate = $m[1];
            }

            // Parse Location
            $cleanLocName = strip_tags($rawLoc);
            $cleanLocName = explode('<br>', $rawLoc)[0];
            $cleanLocName = trim(substr(strip_tags($cleanLocName), 0, 50)) ?: 'Yard KM 12';

            $stmtLoc->execute([':name' => $cleanLocName]);
            $stmtLocGet->execute([':name' => $cleanLocName]);
            $locId = $stmtLocGet->fetchColumn() ?: null;

            // Map Status Enum
            $status = strtoupper($item['status'] ?? 'READY');
            if (!in_array($status, ['READY', 'OPERATING', 'STANDBY', 'INSPEKSI', 'BREAKDOWN', 'ACCIDENT_HOLD', 'INACTIVE'])) {
                $status = 'READY';
            }

            $stmtAsset->execute([
                ':asset_id' => $rawId,
                ':code'     => $code,
                ':sn'       => $sn,
                ':plate'    => $plate,
                ':type'     => $item['type'] ?? 'Heavy Equipment',
                ':cat'      => $item['category'] ?? 'Excavator',
                ':status'   => $status,
                ':loc_id'   => $locId,
                ':raw_loc'  => $rawLoc
            ]);
        }
        echo "Seeded Assets & Locations.\n";
    }

    private function seedWorkOrders(array $workOrders): void {
        $stmt = $this->db->prepare("
            INSERT INTO work_orders (wo_id, asset_id, raw_location, issue_description, downtime_formatted, downtime_minutes, status, priority, assigned_mechanic)
            VALUES (:wo_id, :asset_id, :raw_loc, :issue, :down_fmt, :down_min, :status, :prio, :pic)
            ON DUPLICATE KEY UPDATE 
                status = VALUES(status), 
                downtime_minutes = VALUES(downtime_minutes)
        ");

        foreach ($workOrders as $wo) {
            $rawDown = $wo['downtime'] ?? '';
            $minutes = 0;

            // Parse Downtime String
            if (strpos($rawDown, '#') === false) {
                $hours = 0; $mins = 0;
                if (preg_match('/(\d+)\s*jam/i', $rawDown, $m)) $hours = (int)$m[1];
                if (preg_match('/(\d+)\s*menit/i', $rawDown, $m)) $mins = (int)$m[1];
                $minutes = ($hours * 60) + $mins;
            }

            $stmt->execute([
                ':wo_id'    => $wo['woId'],
                ':asset_id' => $wo['assetId'],
                ':raw_loc'  => $wo['location'] ?? '',
                ':issue'    => $wo['issue'] ?? '',
                ':down_fmt' => $rawDown,
                ':down_min' => $minutes,
                ':status'   => $wo['status'] ?? 'Open',
                ':prio'     => $wo['priority'] ?? 'Normal',
                ':pic'      => $wo['assignedTo'] ?? 'Belum ada PIC'
            ]);
        }
        echo "Seeded Work Orders.\n";
    }

    private function seedCosts(array $costs): void {
        if (isset($costs['budget']) && isset($costs['actual']) && isset($costs['labels'])) {
            $stmt = $this->db->prepare("
                INSERT INTO cost_financial_monthly (month_label, year_period, budget_amount, actual_amount)
                VALUES (:label, 2026, :budget, :actual)
            ");
            for ($i = 0; $i < count($costs['labels']); $i++) {
                $stmt->execute([
                    ':label'  => $costs['labels'][$i],
                    ':budget' => $costs['budget'][$i] ?? 0,
                    ':actual' => $costs['actual'][$i] ?? 0
                ]);
            }
        }

        if (isset($costs['unit_valuations'])) {
            $stmtVal = $this->db->prepare("
                INSERT INTO unit_valuations (asset_id, unit_name, unit_code_alias, total_repair_cost, purchase_price, book_value, market_price_min, market_price_max, raw_market_price_str)
                VALUES (:asset_id, :unit_name, :alias, :repair, :buy, :book, :mkt_min, :mkt_max, :raw_mkt)
            ");

            foreach ($costs['unit_valuations'] as $uv) {
                $repair = $this->cleanMoney($uv['total_perbaikan'] ?? 0);
                $buy    = $this->cleanMoney($uv['harga_beli'] ?? 0);
                $book   = $this->cleanMoney($uv['nilai_buku'] ?? 0);
                
                $rawMkt = $uv['harga_pasaran'] ?? '';
                $mktMin = 0; $mktMax = 0;
                if (preg_match('/([\d\.]+)\s*[\–\-]\s*([\d\.]+)/u', $rawMkt, $m)) {
                    $mktMin = $this->cleanMoney($m[1]);
                    $mktMax = $this->cleanMoney($m[2]);
                } else {
                    $mktMin = $this->cleanMoney($rawMkt);
                    $mktMax = $mktMin;
                }

                $stmtVal->execute([
                    ':asset_id'  => $uv['id'] ?? null,
                    ':unit_name' => $uv['unit'] ?? 'Unknown Unit',
                    ':alias'     => $uv['id'] ?? null,
                    ':repair'    => $repair,
                    ':buy'       => $buy,
                    ':book'      => $book,
                    ':mkt_min'   => $mktMin,
                    ':mkt_max'   => $mktMax,
                    ':raw_mkt'   => $rawMkt
                ]);
            }
        }
        echo "Seeded Financial Costs & Valuations.\n";
    }

    private function cleanMoney($val): float {
        if (is_numeric($val)) return (float)$val;
        $clean = preg_replace('/[^\d]/', '', (string)$val);
        return (float)($clean ?: 0);
    }
}

// CLI Execution
if (php_sapi_name() === 'cli') {
    $seeder = new SeederDataJson();
    $seeder->run();
}
