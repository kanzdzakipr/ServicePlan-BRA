# Blueprint Implementation Plan 2: Backend Architecture, Database Schema (MySQL/PDO), and Data Migration Strategy (ServicePlan-BRA)

> **Status Progress Backend & Database (26 Juli 2026)**:
> - **Production Database Script**: Berkas [scripts/schema.sql](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/scripts/schema.sql) telah sukses dibuat dan terpopulasi dengan 17 DDL tabel relasional lengkap, indeks performa, aturan `NULL`, serta data *initial seeders* DML otentik.
> - **ETL CLI Migration Engine**: Berkas [scripts/SeederDataJson.php](file:///c:/Users/DerpyPotatoes8/Downloads/vscode/widya/ServicePlan-BRA/scripts/SeederDataJson.php) telah siap dieksekusi secara otomatis dari berkas `data.json` ke MySQL.
> - **Backend PHP Inventory**: Inventarisasi 4 Helper Core, 13 PDO Models (`/models/`), dan 14 API Controllers (`/api/`) telah didokumentasikan dan disiapkan untuk tahap perbintangan integrasi frontend `dashboard.html`.

Dokumen ini merupakan panduan teknis mendalam untuk pembangunan *Backend Engine* menggunakan **PHP Data Objects (PDO)**, perancangan skema basis data **MySQL**, serta strategi transisi (*migration & ETL engine*) untuk mengubah data *dummy*/legacy dari `data.json` dan berkas spreadsheet `material/` menjadi basis data relasional yang aman, terstruktur, dan siap pakai.

---

## 1. Arsitektur Backend (PHP PDO Engine)

### 1.1 Pola Arsitektur & Topology System
Backend dirancang menggunakan pola **RESTful API Architecture** berbasis PHP 8.x Native (tanpa framework berat) atau Micro-framework (seperti Slim/Lumen) yang mengedepankan performa tinggi, efisiensi memori, dan fleksibilitas.

```
[ Frontend Client (dashboard.html / JS) ]
                   │
                   ▼ (HTTP JSON Request / Bearer Token)
[ Router / Controller Layer (api/*.php) ]
                   │
                   ▼
  [ Service & Validation Layer ]
                   │
                   ▼
[ Data Access Layer (PDO Models) ]
                   │
                   ▼ (Prepared Statements / Prepared Queries)
   [ MySQL Database (ServicePlan DB) ]
```

### 1.2 Struktur Folder Project Backend
```
ServicePlan-BRA/
├── api/                        # API Endpoints (Controllers)
│   ├── auth.php
│   ├── dashboard.php
│   ├── assets.php
│   ├── work_orders.php
│   ├── pm.php
│   ├── spareparts.php
│   ├── condition.php
│   ├── fuel.php
│   ├── costs.php
│   ├── kpi.php
│   ├── accidents.php
│   ├── approvals.php
│   └── reports.php
├── config/                     # Configuration
│   ├── database.php            # PDO Database Connection
│   └── constants.php           # App Constants & JWT Keys
├── core/                       # Core Framework Helpers
│   ├── Database.php            # Singleton PDO Connection Handler
│   ├── Response.php            # JSON Standard Response Formatter
│   ├── AuthMiddleware.php      # JWT / Session Verification
│   └── Validator.php           # Input Data Sanitization & Rules
├── models/                     # PDO Data Access Objects (DAO)
│   ├── AssetModel.php
│   ├── WorkOrderModel.php
│   ├── InspectionModel.php
│   ├── MaintenanceModel.php
│   ├── InventoryModel.php
│   ├── ComponentModel.php
│   ├── FuelModel.php
│   ├── CostModel.php
│   ├── KPIModel.php
│   ├── AccidentModel.php
│   ├── ApprovalModel.php
│   └── UserModel.php
├── scripts/                    # Migration & ETL Scripts
│   ├── schema.sql              # DDL Table Definitions
│   └── SeederDataJson.php      # Migration script for data.json
└── uploads/                    # File Storage (Photos, PDF attachments)
```

### 1.3 Manajemen Koneksi Database (`Database.php` PDO Singleton)

Penggunaan pola **Singleton Pattern** memastikan hanya satu koneksi PDO yang dibuka per *request lifecycle*, menghemat sumber daya *database pool*.

```php
<?php
// core/Database.php

class Database {
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: 'localhost';
            $db   = getenv('DB_NAME') ?: 'serviceplan_bra';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') ?: '';
            $charset = 'utf8mb4';

            $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Database Connection Failed: ' . $e->getMessage()
                ]);
                exit;
            }
        }
        return self::$instance;
    }
}
```

### 1.4 Keamanan, Autentikasi Multi-Role, dan Middleware RBAC
1. **Prepared Statements Strict Rule**: Seluruh query SQL wajib menggunakan *parameterized queries* (`PDOStatement::bindValue` atau `execute([$params])`) untuk menghapus risiko **SQL Injection**.
2. **Input Sanitization**: Seluruh payload request melewati `Validator::sanitize()` untuk mencegah serangan **XSS (Cross-Site Scripting)**.
3. **Many-to-Many Multi-Role Assignment Architecture**:
   * Setiap akun pengguna (`user_id`) didukung oleh tabel junction **`user_roles`** (`user_id`, `role_id`, `is_primary`) yang memungkinkan satu pengguna memegang **lebih dari satu peran (*Multi-Role*)** secara bersamaan (contoh: *Equipment Manager* merangkap *Maintenance Planner*, atau *Welder* merangkap *Mekanik Senior*).
   * Pada saat autentikasi, `AuthMiddleware::verifyToken()` mengekstrak seluruh peran aktif pengguna dan menggabungkan (*union*) seluruh kewenangan aksi granular dari tabel **`permissions`** (`permission_key`, `menu_slug`, `action_type`) dan **`role_permissions`**.
   * Antarmuka frontend `dashboard.html` menerima klaim token JWT `active_roles` dan `permissions` untuk mengontrol visibilitas menu navbar (misal: menyembunyikan menu *Biaya* dan *Approval Inbox* dari peran yang tidak berwenang) serta menonaktifkan tombol aksi (*Create, Edit, Approve, Override*).
4. **Transaction Integrity**: Transaksi kompleks (seperti penutupan WO yang mengubah status unit, mencatat stok spare part, dan memperbarui jam kerja mekanik) wajib dibungkus dalam `PDO::beginTransaction()`, `PDO::commit()`, dan `PDO::rollBack()`.

---

## 2. Inventarisasi File Class PDO & Layer Backend API

Tabel berikut merinci seluruh kelas PDO Model yang akan dibuat beserta fungsionalitas method utama dan endpoint API yang dilayani.

| File PDO Model (`/models/`) | File Controller (`/api/`) | Primary Responsibility & Method Core | Affected Frontend View (`dashboard.html`) |
|:---|:---|:---|:---|
| `AssetModel.php` | `assets.php` | `getAll()`, `getById()`, `create()`, `updateStatus()`, `get360Details()`, `getNonReadyAssets()` | `#view-dashboard`, `#view-monitoring`, `#view-asset` |
| `WorkOrderModel.php` | `work_orders.php` | `getKanbanBoard()`, `createWO()`, `updateStatus()`, `logMechanicHours()`, `verifyClosing()` | `#view-dashboard`, `#view-wo` |
| `InspectionModel.php` | `inspections.php` | `submitP2H()`, `getInspectionHistory()`, `flagCriticalFindings()` | `#view-uc` (Inspeksi & P2H) |
| `MaintenanceModel.php` | `pm.php` | `getPMForecast()`, `schedulePM()`, `completePM()`, `checkOverdue()` | `#view-uc` (Preventive Maintenance) |
| `InventoryModel.php` | `spareparts.php` | `searchParts()`, `submitSPB()`, `reserveStock()`, `issuePartToWO()`, `getLeadTimeSLA()` | `#view-uc` (Spare Part & Logistik) |
| `ComponentModel.php` | `condition.php` | `getTireLayout()`, `updateTireMeasurement()`, `getComponentHealth()`, `logGreasing()` | `#view-uc` (Condition Monitoring) |
| `FuelModel.php` | `fuel.php` | `logRefuel()`, `getLPHReport()`, `detectFuelAnomaly()` | `#view-uc` (Fuel Management) |
| `CostModel.php` | `costs.php` | `getBudgetVsActual()`, `getUnitValuations()`, `logTransaction()` | `#view-biaya` |
| `KPIModel.php` | `kpi.php` | `getMechanicLeaderboard()`, `calculateMTTR_MTBF()`, `getOvertimeSPL()` | `#view-uc` (People & KPI, Produktivitas) |
| `AccidentModel.php` | `accidents.php` | `reportAccident()`, `updateCAPA()`, `releaseUnitHold()` | `#view-uc` (HSE / Accident) |
| `ApprovalModel.php` | `approvals.php` | `getPendingInbox()`, `approveDocument()`, `rejectDocument()` | `#view-uc` (Approval) |
| `UserModel.php` | `auth.php` | `authenticate()`, `getPermissions()`, `getLocations()` | `#view-uc` (Pengaturan, Top Header) |

---

## 3. Desain Database Relasional & Spesifikasi DDL (MySQL)

### 3.1 DDL SQL Script Lengkap (`schema.sql`)

Berikut adalah spesifikasi DDL MySQL 8.0/MariaDB yang mencakup tipe data, primary key, foreign key, index performance, dan constraints.

```sql
-- ============================================================================
-- SYSTEM INFORMATION EQUIPMENT PT BINA REKAYASA ANUGRAH (SERVICEPLAN-BRA)
-- DATABASE SCHEMA DDL DEFINITION
-- ============================================================================

CREATE DATABASE IF NOT EXISTS serviceplan_bra 
DEFAULT CHARACTER SET utf8mb4 
DEFAULT COLLATE utf8mb4_unicode_ci;

USE serviceplan_bra;

-- 1. MASTER LOCATIONS
CREATE TABLE IF NOT EXISTS locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    location_name VARCHAR(100) NOT NULL UNIQUE,
    location_type ENUM('Yard', 'Pit', 'Borrow Pit', 'Workshop', 'Site Area') DEFAULT 'Site Area',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. USERS & ROLES
CREATE TABLE IF NOT EXISTS roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role_id INT NOT NULL,
    assigned_location_id INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id),
    FOREIGN KEY (assigned_location_id) REFERENCES locations(location_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 3. MASTER ASSETS
CREATE TABLE IF NOT EXISTS assets (
    asset_id VARCHAR(100) PRIMARY KEY, -- e.g., 'DZ-00002', 'DT-00049'
    asset_code VARCHAR(50) NOT NULL,
    serial_number VARCHAR(100) NULL,
    license_plate VARCHAR(50) NULL,
    type VARCHAR(50) DEFAULT 'Heavy Equipment',
    category ENUM('Excavator', 'Bulldozer', 'Dump Truck', 'Motor Grader', 'Vibro Compactor', 'Water Truck', 'Other') NOT NULL,
    make_model VARCHAR(100) NULL,
    year_manufacture INT NULL,
    ownership ENUM('Milik Sendiri', 'Sewa', 'Leasing') DEFAULT 'Milik Sendiri',
    status ENUM('READY', 'OPERATING', 'STANDBY', 'INSPEKSI', 'BREAKDOWN', 'ACCIDENT_HOLD', 'INACTIVE') DEFAULT 'READY',
    current_location_id INT NULL,
    raw_location_notes TEXT NULL, -- Preserving raw notes from data.json
    last_hm_km DECIMAL(10,2) DEFAULT 0.00,
    last_update_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (current_location_id) REFERENCES locations(location_id) ON DELETE SET NULL,
    INDEX idx_asset_status (status),
    INDEX idx_asset_category (category)
) ENGINE=InnoDB;

-- 4. ASSET MOVEMENTS & BAST
CREATE TABLE IF NOT EXISTS asset_movements (
    movement_id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id VARCHAR(100) NOT NULL,
    from_location_id INT NULL,
    to_location_id INT NOT NULL,
    bast_number VARCHAR(100) NULL,
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL,
    requested_by INT NULL,
    FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE,
    FOREIGN KEY (from_location_id) REFERENCES locations(location_id),
    FOREIGN KEY (to_location_id) REFERENCES locations(location_id)
) ENGINE=InnoDB;

-- 5. WORK ORDERS (WO)
CREATE TABLE IF NOT EXISTS work_orders (
    wo_id VARCHAR(50) PRIMARY KEY, -- e.g., 'WO-26-347'
    asset_id VARCHAR(100) NOT NULL,
    location_id INT NULL,
    raw_location VARCHAR(255) NULL,
    issue_description TEXT NOT NULL,
    downtime_formatted VARCHAR(100) NULL, -- Raw downtime from legacy
    downtime_minutes INT DEFAULT 0,
    is_downtime BOOLEAN DEFAULT TRUE,
    status ENUM('Open', 'In Progress', 'Waiting Part', 'Testing', 'Closed', 'Cancelled') DEFAULT 'Open',
    priority ENUM('Normal', 'High') DEFAULT 'Normal',
    assigned_mechanic VARCHAR(100) DEFAULT 'Belum ada PIC',
    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    repair_started_at TIMESTAMP NULL,
    closed_at TIMESTAMP NULL,
    verification_supervisor_id INT NULL,
    before_photo_url VARCHAR(255) NULL,
    after_photo_url VARCHAR(255) NULL,
    FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(location_id) ON DELETE SET NULL,
    INDEX idx_wo_status (status),
    INDEX idx_wo_priority (priority)
) ENGINE=InnoDB;

-- 6. WO TIME LOGS (MECHANIC HOURS)
CREATE TABLE IF NOT EXISTS wo_time_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    wo_id VARCHAR(50) NOT NULL,
    mechanic_user_id INT NOT NULL,
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP NULL,
    normal_hours DECIMAL(5,2) DEFAULT 0.00,
    overtime_hours DECIMAL(5,2) DEFAULT 0.00,
    spl_number VARCHAR(50) NULL,
    activity_description TEXT NULL,
    FOREIGN KEY (wo_id) REFERENCES work_orders(wo_id) ON DELETE CASCADE,
    FOREIGN KEY (mechanic_user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- 7. INSPECTIONS & P2H
CREATE TABLE IF NOT EXISTS inspections (
    inspection_id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id VARCHAR(100) NOT NULL,
    inspector_id INT NOT NULL,
    inspection_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    current_hm_km DECIMAL(10,2) NOT NULL,
    overall_result ENUM('PASS', 'WARNING', 'FAIL') DEFAULT 'PASS',
    created_wo_id VARCHAR(50) NULL,
    FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE,
    FOREIGN KEY (inspector_id) REFERENCES users(user_id),
    FOREIGN KEY (created_wo_id) REFERENCES work_orders(wo_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 8. PREVENTIVE MAINTENANCE PLANS
CREATE TABLE IF NOT EXISTS pm_plans (
    pm_plan_id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id VARCHAR(100) NOT NULL,
    interval_hm INT NOT NULL, -- 250, 500, 1000, 2000
    last_service_hm DECIMAL(10,2) DEFAULT 0.00,
    next_due_hm DECIMAL(10,2) NOT NULL,
    status ENUM('PLANNED', 'DUE_SOON', 'OVERDUE', 'IN_PROGRESS', 'COMPLETED') DEFAULT 'PLANNED',
    FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. PARTS INVENTORY & SPB
CREATE TABLE IF NOT EXISTS parts (
    part_id INT AUTO_INCREMENT PRIMARY KEY,
    part_number VARCHAR(100) NOT NULL UNIQUE,
    part_name VARCHAR(150) NOT NULL,
    unit_measure VARCHAR(20) DEFAULT 'Pcs',
    stock_qty INT DEFAULT 0,
    min_stock_qty INT DEFAULT 2,
    unit_cost DECIMAL(15,2) DEFAULT 0.00
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchase_requests (
    spb_id VARCHAR(50) PRIMARY KEY,
    wo_id VARCHAR(50) NOT NULL,
    requested_by INT NOT NULL,
    urgency ENUM('Normal', 'Emergency') DEFAULT 'Normal',
    status ENUM('Draft', 'Submitted', 'Approved', 'Ordered', 'Issued', 'Closed') DEFAULT 'Submitted',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (wo_id) REFERENCES work_orders(wo_id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- 10. CONDITION MONITORING (TIRES & COMPONENTS)
CREATE TABLE IF NOT EXISTS tire_inspections (
    tire_inspection_id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id VARCHAR(100) NOT NULL,
    tire_position VARCHAR(20) NOT NULL, -- e.g., 'FL', 'FR', 'R1L', 'R1R'
    tread_depth_mm DECIMAL(4,2) NOT NULL,
    air_pressure_psi INT NOT NULL,
    condition_color ENUM('GREEN', 'YELLOW', 'RED') NOT NULL,
    inspected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 11. FUEL LOGS
CREATE TABLE IF NOT EXISTS fuel_logs (
    fuel_log_id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id VARCHAR(100) NOT NULL,
    refuel_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    flowmeter_start DECIMAL(10,2) NOT NULL,
    flowmeter_end DECIMAL(10,2) NOT NULL,
    liters_issued DECIMAL(10,2) NOT NULL,
    current_hm_km DECIMAL(10,2) NOT NULL,
    calculated_lph DECIMAL(6,2) NULL,
    is_anomaly BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 12. COST TRANSACTIONS & UNIT VALUATIONS
CREATE TABLE IF NOT EXISTS cost_financial_monthly (
    cost_id INT AUTO_INCREMENT PRIMARY KEY,
    month_label VARCHAR(20) NOT NULL, -- 'Mei', 'Jun', 'Jul', etc.
    year_period INT NOT NULL DEFAULT 2026,
    budget_amount DECIMAL(15,2) NOT NULL,
    actual_amount DECIMAL(15,2) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS unit_valuations (
    valuation_id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id VARCHAR(100) NULL,
    unit_name VARCHAR(150) NOT NULL,
    unit_code_alias VARCHAR(50) NULL,
    total_repair_cost DECIMAL(15,2) DEFAULT 0.00,
    purchase_price DECIMAL(15,2) DEFAULT 0.00,
    book_value DECIMAL(15,2) DEFAULT 0.00,
    market_price_min DECIMAL(15,2) DEFAULT 0.00,
    market_price_max DECIMAL(15,2) DEFAULT 0.00,
    raw_market_price_str VARCHAR(100) NULL
) ENGINE=InnoDB;

-- 13. ACCIDENTS & CAPA
CREATE TABLE IF NOT EXISTS accidents (
    accident_id VARCHAR(50) PRIMARY KEY,
    asset_id VARCHAR(100) NOT NULL,
    incident_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    location_id INT NULL,
    severity ENUM('Minor', 'Moderate', 'Critical') DEFAULT 'Minor',
    description TEXT NOT NULL,
    estimated_damage_cost DECIMAL(15,2) DEFAULT 0.00,
    is_unit_locked BOOLEAN DEFAULT TRUE,
    status ENUM('Reported', 'Investigating', 'CAPA_Pending', 'Closed') DEFAULT 'Reported',
    FOREIGN KEY (asset_id) REFERENCES assets(asset_id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(location_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 14. APPROVAL INBOX
CREATE TABLE IF NOT EXISTS approvals (
    approval_id INT AUTO_INCREMENT PRIMARY KEY,
    document_type ENUM('SPB', 'WO', 'ACCIDENT_RELEASE', 'MUTASI') NOT NULL,
    document_id VARCHAR(50) NOT NULL,
    approver_user_id INT NOT NULL,
    status ENUM('PENDING', 'APPROVED', 'REJECTED') DEFAULT 'PENDING',
    rejection_reason TEXT NULL,
    action_at TIMESTAMP NULL,
    FOREIGN KEY (approver_user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;
```

---

## 4. Strategi Transisi Data Dummy (`data.json`) ke Backend Database (ETL Engine)

### 4.1 Analisis Anomalies & Messy Data pada `data.json`

Berdasarkan penelusuran fisik berkas `data.json` (7.104 baris):

1. **Complex Combined ID Field (`assets.id`)**:
   * *Contoh raw*: `"DZ-00002 SN P6G01656"`, `"DT-00049 - B 9104 ZYT"`, `"DT-04058 - BM 9678 JO - DT-41005"`.
   * *Masalah*: ID menggabungkan Kode Lambung, Serial Number, dan Nomor Polisi dalam satu string bebas.
2. **Messy HTML Location Strings (`assets.location`)**:
   * *Contoh raw*: `"Bropit harpaan baru.<br>hujan jm 08.00 wib<br>Aktivitas mekanik terhambat... 22/12/2025 DIBAWA KE BENGKEL ANUGRA"`.
   * *Masalah*: Lokasi memuat tag `<br>`, catatan cuaca, tanggal mutasi, dan histori kronologis.
3. **Legacy Downtime String Format (`work_orders.downtime`)**:
   * *Contoh raw*: `"0 jam 49 menit"`, `"10 jam 15 menit"`, versus `"#########################"`.
   * *Masalah*: Downtime untuk WO aktif/open diisi string hashtag `#########################`, sedangkan WO closed diisi string teks jam/menit.
4. **Currency & Range Formatting (`costs.unit_valuations`)**:
   * *Contoh raw*: `total_perbaikan: "Rp421.415.277"`, `harga_pasaran: "450.000.000 – 520.000.000"`, atau `"400.000.00 – 580.000.000"`.
   * *Masalah*: Teks mengandung simbol `Rp`, titik ribuan, kesalahan penulisan nol, dan tanda hubung rentang harga (*range*).

### 4.2 Aturan Transformasi Data & Parsing Rules (Cleaning Matrix)

| Source Field (`data.json`) | Target Table & Column | Cleaning & Transformation Algorithm |
|:---|:---|:---|
| `assets[].id` | `assets.asset_id`, `assets.serial_number`, `assets.license_plate` | Regex Matcher: Ekstrak bagian pertama sebagai `asset_id` (misal `DZ-00002`). Ekstrak substring setelah "SN" menjadi `serial_number`. Ekstrak format plat nomor (misal `B 9104 ZYT`) menjadi `license_plate`. |
| `assets[].location` | `locations.location_name`, `assets.raw_location_notes` | Strip tag HTML `<br>`. Ekstrak 50 karakter pertama hingga tanda titik sebagai `location_name` (misal "Bropit Harapan Baru", "Yard KM 12", "Minas"). Simpan seluruh teks asli di `raw_location_notes`. |
| `work_orders[].downtime` | `work_orders.downtime_minutes`, `work_orders.downtime_formatted` | Simpan string asli di `downtime_formatted`. Jika string mengandung `#`, set `downtime_minutes = NULL` (WO masih berjalan). Jika memuat jam/menit, parse dengan Regex: `(X jam * 60) + Y menit` = total menit integer. |
| `costs.unit_valuations` | `unit_valuations.total_repair_cost`, `market_price_min`, `market_price_max` | Hapus "Rp", titik, dan spasi. Jika berupa range (mengandung `–` atau `-`), split menjadi dua angka. Set `market_price_min` = angka kiri, `market_price_max` = angka kanan. |

### 4.3 Spesifikasi Algoritma Script Migration / Seeder (`SeederDataJson.php`)

Script migration backend PHP berikut mengeksekusi ETL secara mandiri dari berkas `data.json` ke MySQL.

```php
<?php
// scripts/SeederDataJson.php

require_once __DIR__ . '/../core/Database.php';

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
```

---

## 5. Prompt Dukungan AI (Production-Ready Code Generation Prompts)

Berikut adalah *prompt* siap pakai untuk menghasilkan kode backend PHP PDO, berkas schema SQL, dan skrip migrasi ETL secara otomatis.

### Prompt 1: Generasi Class Model PDO PHP & REST Controller
> "Buatkan struktur class PHP PDO Model `AssetModel.php` dan API Controller `assets.php` berbasis PHP 8 native. `AssetModel.php` harus menggunakan Singleton Database connection dari `core/Database.php` dan memiliki method: `getAll(array $filters)`, `getById(string $id)`, `get360Details(string $id)`, dan `updateStatus(string $id, string $status)`. Controller `assets.php` harus menangani request HTTP GET/POST, mengembalikan JSON standard `{success: true, data: [...]}` dan menggunakan prepared statements strict."

### Prompt 2: Generasi Complete DDL Migration SQL Script
> "Tuliskan file `schema.sql` MySQL 8.0 lengkap yang membuat database `serviceplan_bra` beserta seluruh tabel relasionalnya: `locations`, `users`, `roles`, `assets`, `asset_movements`, `work_orders`, `wo_time_logs`, `inspections`, `pm_plans`, `parts`, `purchase_requests`, `tire_inspections`, `fuel_logs`, `cost_financial_monthly`, `unit_valuations`, `accidents`, dan `approvals`. Sertakan Primary Key, Foreign Key Constraints (CASCADE/SET NULL), Indexing pada kolom pencarian status/kategori, dan charset utf8mb4."

### Prompt 3: Generasi Script Migration ETL Data JSON (`SeederDataJson.php`)
> "Buatkan script CLI PHP `scripts/SeederDataJson.php` untuk membaca file `data.json` dan menyuntikkan datanya ke database MySQL `serviceplan_bra`. Script harus menggunakan PDO Transaction (`beginTransaction`, `commit`, `rollBack`), menyertakan logika Regex cleaning untuk ekstrak `asset_id`, `serial_number`, dan `license_plate` dari field `id` legacy, membersihkan tag `<br>` pada lokasi, mengonversi string downtime menjadi total menit integer, dan mengonversi format Rupiah pada `unit_valuations` menjadi nilai float/decimal SQL."
