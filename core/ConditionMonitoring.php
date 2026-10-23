<?php
declare(strict_types=1);

final class ConditionMonitoring
{
    public static function ensureTables(PDO $db): void
    {
        $db->exec("CREATE TABLE IF NOT EXISTS grease_logs (
            grease_log_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            asset_id VARCHAR(100) NOT NULL,
            current_hm DECIMAL(10,2) NOT NULL,
            last_grease_hm DECIMAL(10,2) NOT NULL,
            interval_hm DECIMAL(10,2) NOT NULL,
            quantity_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
            grease_type VARCHAR(100) NOT NULL,
            lubrication_points JSON NOT NULL,
            notes VARCHAR(500) NULL,
            status_level ENUM('success','warning','danger') NOT NULL,
            inspected_by INT NOT NULL,
            inspected_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_grease_asset_date (asset_id, inspected_at),
            CONSTRAINT fk_grease_asset FOREIGN KEY (asset_id) REFERENCES assets (asset_id) ON DELETE CASCADE,
            CONSTRAINT fk_grease_inspector FOREIGN KEY (inspected_by) REFERENCES users (user_id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        self::ensureColumns($db, 'tire_inspections', [
            'physical_condition' => 'VARCHAR(30) NULL',
            'wear_pattern' => 'VARCHAR(40) NULL',
            'recommended_action' => 'VARCHAR(50) NULL',
            'status_level' => "ENUM('success','warning','danger') NULL",
            'notes' => 'VARCHAR(500) NULL',
            'inspected_by' => 'INT NULL',
        ]);
        self::ensureNullableColumn($db, 'tire_inspections', 'tread_depth_mm', 'DECIMAL(4,2) NULL');
        self::ensureIndex(
            $db,
            'tire_inspections',
            'idx_tire_asset_position_date',
            'KEY idx_tire_asset_position_date (asset_id, tire_position, inspected_at)'
        );

        self::ensureColumns($db, 'battery_logs', [
            'battery_type' => 'VARCHAR(100) NULL',
            'cca_percent' => 'DECIMAL(5,2) NULL',
            'terminal_condition' => 'VARCHAR(40) NULL',
            'electrolyte_level' => 'VARCHAR(50) NULL',
            'case_condition' => 'VARCHAR(40) NULL',
            'notes' => 'VARCHAR(500) NULL',
            'status_level' => "ENUM('success','warning','danger') NULL",
            'inspected_by' => 'INT NULL',
            'inspected_at' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        ]);
        self::ensureIndex(
            $db,
            'battery_logs',
            'idx_battery_asset_date',
            'KEY idx_battery_asset_date (asset_id, inspected_at)'
        );
    }

    public static function save(PDO $db, string $domain, array $data, int $actorId): array
    {
        $assetId = self::requiredText($data['asset_id'] ?? null, 'Unit', 100);
        $asset = $db->prepare('SELECT asset_id FROM assets WHERE asset_id = :asset_id');
        $asset->execute([':asset_id' => $assetId]);
        if ($asset->fetchColumn() === false) {
            throw new DomainException("Unit {$assetId} tidak ditemukan pada Master Asset.");
        }

        return match ($domain) {
            'tire' => self::saveTire($db, $assetId, $data, $actorId),
            'grease' => self::saveGrease($db, $assetId, $data, $actorId),
            'battery' => self::saveBattery($db, $assetId, $data, $actorId),
            default => throw new DomainException('Domain Condition Monitoring tidak dikenali.'),
        };
    }

    private static function saveTire(PDO $db, string $assetId, array $data, int $actorId): array
    {
        $position = self::requiredText($data['position'] ?? null, 'Posisi ban', 20);
        if (!preg_match('/^[A-Z0-9-]{1,20}$/', $position)) {
            throw new DomainException('Kode posisi ban tidak valid.');
        }
        $physical = self::choice(
            $data['physical_condition'] ?? null,
            'Kondisi fisik',
            ['Baik', 'DG', 'Retak', 'Benjol', 'Sobek']
        );
        $wear = self::choice(
            $data['wear_pattern'] ?? null,
            'Pola keausan',
            ['Merata', 'Aus tidak rata', 'Aus bahu', 'Aus tengah', 'Rotasi']
        );
        $action = self::choice(
            $data['recommended_action'] ?? null,
            'Rekomendasi',
            ['Monitor', 'Atur tekanan', 'Rotasi', 'Ganti terencana', 'Ganti segera']
        );
        $tread = self::optionalDecimal($data['tread_depth_mm'] ?? null, 'Tread depth', 0, 99.99);
        if ($physical === 'Baik' && $tread === null) {
            throw new DomainException('Tread depth wajib diisi ketika kondisi fisik ban Baik.');
        }
        $pressure = self::decimal($data['air_pressure_psi'] ?? null, 'Tekanan ban', 0, 300);
        $serial = self::optionalText($data['serial_no'] ?? null, 100);
        $notes = self::optionalText($data['notes'] ?? null, 500);

        $status = 'success';
        if ($physical !== 'Baik' || ($tread !== null && $tread < 3.2)) {
            $status = 'danger';
        } elseif (($tread !== null && $tread <= 8.5) || $wear !== 'Merata') {
            $status = 'warning';
        }
        $color = ['success' => 'GREEN', 'warning' => 'YELLOW', 'danger' => 'RED'][$status];

        $insert = $db->prepare(
            'INSERT INTO tire_inspections
             (asset_id, tire_serial_no, tire_position, tread_depth_mm, air_pressure_psi,
              condition_color, physical_condition, wear_pattern, recommended_action,
              status_level, notes, inspected_by)
             VALUES
             (:asset_id, :serial, :position, :tread, :pressure,
              :color, :physical, :wear, :action, :status, :notes, :inspected_by)'
        );
        $insert->execute([
            ':asset_id' => $assetId,
            ':serial' => $serial,
            ':position' => $position,
            ':tread' => $tread,
            ':pressure' => (int) round($pressure),
            ':color' => $color,
            ':physical' => $physical,
            ':wear' => $wear,
            ':action' => $action,
            ':status' => $status,
            ':notes' => $notes,
            ':inspected_by' => $actorId,
        ]);
        $recordId = (int) $db->lastInsertId();
        if ($status === 'danger') {
            self::applyDangerStatusToAsset($db, $assetId);
        }

        return [
            'id' => 'CM-TIRE-' . $recordId,
            'recordId' => $recordId,
            'domain' => 'Ban',
            'status' => $status,
            'summary' => $position . ' ' . ($tread === null ? $physical : "{$tread} mm / " . (int) round($pressure) . " PSI") . " · {$action}",
        ];
    }

    private static function saveGrease(PDO $db, string $assetId, array $data, int $actorId): array
    {
        $currentHm = self::decimal($data['current_hm'] ?? null, 'HM aktual', 0, 99999999);
        $lastHm = self::decimal($data['last_grease_hm'] ?? null, 'HM grease terakhir', 0, 99999999);
        $interval = self::decimal($data['interval_hm'] ?? null, 'Interval grease', 0.01, 100000);
        if ($currentHm < $lastHm) {
            throw new DomainException('HM aktual tidak boleh lebih kecil dari HM grease terakhir.');
        }
        $quantity = self::decimal($data['quantity_kg'] ?? null, 'Jumlah grease', 0, 10000);
        $greaseType = self::choice(
            $data['grease_type'] ?? null,
            'Jenis grease',
            ['Lithium EP-2', 'Moly EP-2', 'Calcium Sulfonate', 'OEM Special Grease']
        );
        $points = $data['points'] ?? null;
        if (!is_array($points) || $points === []) {
            throw new DomainException('Titik pelumasan wajib dikirim sebagai daftar.');
        }
        $normalizedPoints = [];
        foreach ($points as $point => $done) {
            $label = self::requiredText($point, 'Nama titik pelumasan', 100);
            $normalizedPoints[$label] = filter_var($done, FILTER_VALIDATE_BOOLEAN);
        }
        $notes = self::optionalText($data['notes'] ?? null, 500);
        $elapsed = $currentHm - $lastHm;
        $status = $elapsed >= $interval ? 'danger' : ($elapsed >= $interval * 0.9 ? 'warning' : 'success');

        $insert = $db->prepare(
            'INSERT INTO grease_logs
             (asset_id, current_hm, last_grease_hm, interval_hm, quantity_kg,
              grease_type, lubrication_points, notes, status_level, inspected_by)
             VALUES
             (:asset_id, :current_hm, :last_hm, :interval_hm, :quantity,
              :grease_type, :points, :notes, :status, :inspected_by)'
        );
        $insert->execute([
            ':asset_id' => $assetId,
            ':current_hm' => $currentHm,
            ':last_hm' => $lastHm,
            ':interval_hm' => $interval,
            ':quantity' => $quantity,
            ':grease_type' => $greaseType,
            ':points' => json_encode($normalizedPoints, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ':notes' => $notes,
            ':status' => $status,
            ':inspected_by' => $actorId,
        ]);
        $recordId = (int) $db->lastInsertId();
        if ($status === 'danger') {
            self::applyDangerStatusToAsset($db, $assetId);
        }

        return [
            'id' => 'CM-GREASE-' . $recordId,
            'recordId' => $recordId,
            'domain' => 'Grease',
            'status' => $status,
            'summary' => self::formatNumber($elapsed) . " HM sejak grease · "
                . self::formatNumber($quantity) . " kg {$greaseType}",
        ];
    }

    private static function saveBattery(PDO $db, string $assetId, array $data, int $actorId): array
    {
        $voltage = self::decimal($data['voltage'] ?? null, 'Tegangan aki', 0.01, 60);
        $cca = self::decimal($data['cca_percent'] ?? null, 'CCA', 0, 100);
        $brand = self::requiredText($data['brand'] ?? null, 'Merek aki', 100);
        $type = self::requiredText($data['battery_type'] ?? null, 'Tipe aki', 100);
        $installationDate = self::requiredDate($data['installation_date'] ?? null, 'Tanggal pemasangan');
        $terminal = self::choice(
            $data['terminal_condition'] ?? null,
            'Kondisi terminal',
            ['Bersih & kencang', 'Kendor', 'Korosi ringan', 'Korosi berat']
        );
        $electrolyte = self::choice(
            $data['electrolyte_level'] ?? null,
            'Level elektrolit',
            ['Normal', 'Rendah', 'Tidak dapat diperiksa (MF)']
        );
        $caseCondition = self::choice(
            $data['case_condition'] ?? null,
            'Kondisi casing',
            ['Baik', 'Retak', 'Bocor', 'Menggembung']
        );
        $notes = self::optionalText($data['notes'] ?? null, 500);
        $status = 'success';
        if ($voltage < 12.0 || $cca < 60 || in_array($caseCondition, ['Retak', 'Bocor'], true)) {
            $status = 'danger';
        } elseif ($voltage < 12.6 || $cca < 80 || str_contains($terminal, 'Korosi')) {
            $status = 'warning';
        }
        $waterLevel = $electrolyte === 'Rendah' ? 'LOW' : 'GOOD';

        $insert = $db->prepare(
            'INSERT INTO battery_logs
             (asset_id, battery_brand, voltage_val, water_level, installation_date,
              battery_type, cca_percent, terminal_condition, electrolyte_level,
              case_condition, notes, status_level, inspected_by)
             VALUES
             (:asset_id, :brand, :voltage, :water_level, :installation_date,
              :battery_type, :cca, :terminal, :electrolyte,
              :case_condition, :notes, :status, :inspected_by)'
        );
        $insert->execute([
            ':asset_id' => $assetId,
            ':brand' => $brand,
            ':voltage' => $voltage,
            ':water_level' => $waterLevel,
            ':installation_date' => $installationDate,
            ':battery_type' => $type,
            ':cca' => $cca,
            ':terminal' => $terminal,
            ':electrolyte' => $electrolyte,
            ':case_condition' => $caseCondition,
            ':notes' => $notes,
            ':status' => $status,
            ':inspected_by' => $actorId,
        ]);
        $recordId = (int) $db->lastInsertId();
        if ($status === 'danger') {
            self::applyDangerStatusToAsset($db, $assetId);
        }

        return [
            'id' => 'CM-BATTERY-' . $recordId,
            'recordId' => $recordId,
            'domain' => 'Aki',
            'status' => $status,
            'summary' => self::formatNumber($voltage) . ' V · ' . self::formatNumber($cca) . "% CCA · {$terminal}",
        ];
    }

    private static function applyDangerStatusToAsset(PDO $db, string $assetId): void
    {
        $db->prepare("
            UPDATE assets 
            SET status = 'INSPEKSI' 
            WHERE asset_id = :aid 
              AND status NOT IN ('BREAKDOWN', 'ACCIDENT_HOLD', 'INACTIVE')
        ")->execute([':aid' => $assetId]);
    }

    private static function ensureColumns(PDO $db, string $table, array $definitions): void
    {
        $statement = $db->prepare(
            'SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name'
        );
        $statement->execute([':table_name' => $table]);
        $existing = array_fill_keys($statement->fetchAll(PDO::FETCH_COLUMN), true);
        foreach ($definitions as $column => $definition) {
            if (isset($existing[$column])) continue;
            self::assertIdentifier($table);
            self::assertIdentifier($column);
            $db->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }

    private static function ensureNullableColumn(PDO $db, string $table, string $column, string $definition): void
    {
        $statement = $db->prepare(
            'SELECT IS_NULLABLE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name'
        );
        $statement->execute([':table_name' => $table, ':column_name' => $column]);
        if ($statement->fetchColumn() === 'YES') return;
        self::assertIdentifier($table);
        self::assertIdentifier($column);
        $db->exec("ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` {$definition}");
    }

    private static function ensureIndex(PDO $db, string $table, string $index, string $definition): void
    {
        $statement = $db->prepare(
            'SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND INDEX_NAME = :index_name'
        );
        $statement->execute([':table_name' => $table, ':index_name' => $index]);
        if ((int) $statement->fetchColumn() > 0) return;
        self::assertIdentifier($table);
        $db->exec("ALTER TABLE `{$table}` ADD {$definition}");
    }

    private static function assertIdentifier(string $value): void
    {
        if (!preg_match('/^[a-z0-9_]+$/i', $value)) {
            throw new RuntimeException('Identifier migrasi Condition Monitoring tidak valid.');
        }
    }

    private static function requiredText($value, string $label, int $maxLength): string
    {
        $text = trim((string) $value);
        if ($text === '') throw new DomainException("{$label} wajib diisi.");
        if (mb_strlen($text) > $maxLength) throw new DomainException("{$label} maksimal {$maxLength} karakter.");
        return $text;
    }

    private static function optionalText($value, int $maxLength): ?string
    {
        $text = trim((string) $value);
        return $text === '' ? null : mb_substr($text, 0, $maxLength);
    }

    private static function choice($value, string $label, array $allowed): string
    {
        $text = self::requiredText($value, $label, 100);
        if (!in_array($text, $allowed, true)) throw new DomainException("{$label} tidak dikenali.");
        return $text;
    }

    private static function decimal($value, string $label, float $minimum, float $maximum): float
    {
        $raw = str_replace(',', '.', trim((string) $value));
        if ($raw === '' || !is_numeric($raw)) throw new DomainException("{$label} harus berupa angka.");
        $number = (float) $raw;
        if (!is_finite($number) || $number < $minimum || $number > $maximum) {
            throw new DomainException("{$label} harus berada pada rentang {$minimum}-{$maximum}.");
        }
        return round($number, 2);
    }

    private static function optionalDecimal($value, string $label, float $minimum, float $maximum): ?float
    {
        if (trim((string) $value) === '') return null;
        return self::decimal($value, $label, $minimum, $maximum);
    }

    private static function requiredDate($value, string $label): string
    {
        $raw = trim((string) $value);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
        if (!$date || $date->format('Y-m-d') !== $raw) throw new DomainException("{$label} tidak valid.");
        if ($date > new DateTimeImmutable('today')) throw new DomainException("{$label} tidak boleh berada di masa depan.");
        return $raw;
    }

    private static function formatNumber(float $number): string
    {
        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }
}
