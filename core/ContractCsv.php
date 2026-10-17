<?php
declare(strict_types=1);

final class ContractCsv
{
    public const HEADERS = [
        'contract_number', 'project', 'site', 'asset_id', 'start_date',
        'end_date', 'target_hours', 'notes',
    ];

    public static function ensureTable(PDO $db): void
    {
        $db->exec("CREATE TABLE IF NOT EXISTS contract_assignments (
            assignment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            contract_number VARCHAR(100) NOT NULL,
            project VARCHAR(150) NOT NULL,
            site VARCHAR(150) NOT NULL,
            asset_id VARCHAR(100) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            target_hours DECIMAL(12,2) NOT NULL,
            notes VARCHAR(500) NULL,
            created_by INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_contract_assignment (contract_number, asset_id, start_date),
            KEY idx_contract_asset_period (asset_id, start_date, end_date),
            CONSTRAINT fk_contract_assignment_asset FOREIGN KEY (asset_id)
                REFERENCES assets (asset_id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public static function parse(string $csv, array $accessibleAssets): array
    {
        $errors = [];
        $rows = [];
        if (strlen($csv) > 1024 * 1024) {
            return ['rows' => [], 'errors' => [['row' => 0, 'message' => 'File CSV maksimal 1 MB.']]];
        }
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv) ?? $csv;
        if (!mb_check_encoding($csv, 'UTF-8')) {
            return ['rows' => [], 'errors' => [['row' => 0, 'message' => 'File harus berformat UTF-8.']]];
        }
        $stream = fopen('php://temp', 'w+');
        if ($stream === false) throw new RuntimeException('Gagal membaca CSV.');
        fwrite($stream, $csv);
        rewind($stream);
        $firstLine = strtok($csv, "\r\n") ?: '';
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        $headers = fgetcsv($stream, 0, $delimiter, '"', '');
        if (!is_array($headers)) {
            fclose($stream);
            return ['rows' => [], 'errors' => [['row' => 1, 'message' => 'Header CSV tidak ditemukan.']]];
        }
        $headers = array_map(static fn($value): string => strtolower(trim((string) $value)), $headers);
        $acceptedHeaders = array_merge(self::HEADERS, ['actual_hours', 'achievement_percent']);
        $missing = array_diff(array_slice(self::HEADERS, 0, 7), $headers);
        $unknown = array_diff($headers, $acceptedHeaders);
        if ($missing || $unknown || count($headers) !== count(array_unique($headers))) {
            fclose($stream);
            return ['rows' => [], 'errors' => [[
                'row' => 1,
                'message' => 'Header tidak sesuai. Wajib: ' . implode(', ', array_slice(self::HEADERS, 0, 7))
                    . ($unknown ? '. Kolom tidak dikenal: ' . implode(', ', $unknown) : ''),
            ]]];
        }

        $line = 1;
        $seen = [];
        while (($values = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
            $line++;
            if ($values === [null] || count(array_filter($values, static fn($value): bool => trim((string) $value) !== '')) === 0) continue;
            if ($line > 1001) {
                $errors[] = ['row' => $line, 'message' => 'Maksimal 1000 baris data per file.'];
                break;
            }
            if (count($values) !== count($headers)) {
                $errors[] = ['row' => $line, 'message' => 'Jumlah kolom tidak sesuai header.'];
                continue;
            }
            $source = array_combine($headers, array_map(static fn($value): string => trim((string) $value), $values));
            $row = [];
            foreach (self::HEADERS as $key) $row[$key] = $source[$key] ?? '';
            foreach (['contract_number' => 100, 'project' => 150, 'site' => 150, 'asset_id' => 100] as $key => $limit) {
                if ($row[$key] === '' || mb_strlen($row[$key]) > $limit) {
                    $errors[] = ['row' => $line, 'message' => "{$key} wajib diisi dan maksimal {$limit} karakter."];
                }
            }
            if (mb_strlen($row['notes']) > 500) $errors[] = ['row' => $line, 'message' => 'notes maksimal 500 karakter.'];
            foreach (['contract_number', 'project', 'site', 'notes'] as $key) {
                if (preg_match('/^\s*[=+@\-]/u', $row[$key])) {
                    $errors[] = ['row' => $line, 'message' => "{$key} tidak boleh diawali rumus spreadsheet."];
                }
            }
            if ($row['asset_id'] !== '' && !isset($accessibleAssets[$row['asset_id']])) {
                $errors[] = ['row' => $line, 'message' => "ID unit {$row['asset_id']} tidak ditemukan atau tidak dapat diakses."];
            }
            foreach (['start_date', 'end_date'] as $key) {
                $date = DateTimeImmutable::createFromFormat('!Y-m-d', $row[$key]);
                if (!$date || $date->format('Y-m-d') !== $row[$key]) {
                    $errors[] = ['row' => $line, 'message' => "{$key} harus berupa tanggal YYYY-MM-DD yang valid."];
                }
            }
            if ($row['start_date'] !== '' && $row['end_date'] !== '' && $row['end_date'] < $row['start_date']) {
                $errors[] = ['row' => $line, 'message' => 'end_date tidak boleh lebih awal dari start_date.'];
            }
            if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $row['target_hours']) || (float) $row['target_hours'] <= 0) {
                $errors[] = ['row' => $line, 'message' => 'target_hours harus angka positif (maksimal 2 desimal).'];
            }
            $identity = $row['contract_number'] . "\0" . $row['asset_id'] . "\0" . $row['start_date'];
            if (isset($seen[$identity])) $errors[] = ['row' => $line, 'message' => "Duplikat kontrak/unit/tanggal mulai pada baris {$seen[$identity]}."];
            $seen[$identity] = $line;
            $rows[] = $row;
        }
        fclose($stream);
        if (!$rows && !$errors) $errors[] = ['row' => 2, 'message' => 'Tidak ada baris kontrak untuk diimpor.'];
        return ['rows' => $rows, 'errors' => $errors];
    }

    public static function import(PDO $db, array $rows, int $actorId): int
    {
        $statement = $db->prepare(
            'INSERT INTO contract_assignments
             (contract_number, project, site, asset_id, start_date, end_date, target_hours, notes, created_by)
             VALUES (:contract_number, :project, :site, :asset_id, :start_date, :end_date, :target_hours, :notes, :created_by)
             ON DUPLICATE KEY UPDATE project = VALUES(project), site = VALUES(site),
                 end_date = VALUES(end_date), target_hours = VALUES(target_hours), notes = VALUES(notes)'
        );
        foreach ($rows as $row) {
            $statement->execute([
                ':contract_number' => $row['contract_number'], ':project' => $row['project'],
                ':site' => $row['site'], ':asset_id' => $row['asset_id'],
                ':start_date' => $row['start_date'], ':end_date' => $row['end_date'],
                ':target_hours' => $row['target_hours'], ':notes' => $row['notes'] ?: null,
                ':created_by' => $actorId,
            ]);
        }
        return count($rows);
    }

    public static function records(PDO $db, string $scopeSql, array $scopeParams): array
    {
        $sql = "SELECT c.contract_number, c.project, c.site, c.asset_id, c.start_date,
                       c.end_date, c.target_hours, c.notes, a.asset_code,
                       COALESCE((SELECT ROUND(SUM(o.hm_operation), 2)
                                 FROM report_operation_logs o
                                 INNER JOIN report_records r ON r.report_id = o.report_id
                                 WHERE o.asset_id = c.asset_id
                                   AND o.operation_date BETWEEN c.start_date AND c.end_date
                                   AND o.reversed_at IS NULL AND r.status = 'FINAL'), 0) AS actual_hours
                FROM contract_assignments c
                INNER JOIN assets a ON a.asset_id = c.asset_id";
        if ($scopeSql !== '') $sql .= ' WHERE ' . $scopeSql;
        $sql .= ' ORDER BY c.start_date DESC, c.contract_number, c.asset_id LIMIT 1000';
        $statement = $db->prepare($sql);
        $statement->execute($scopeParams);
        return array_map(static function (array $row): array {
            $target = (float) $row['target_hours'];
            $actual = (float) $row['actual_hours'];
            return [
                'contract_number' => (string) $row['contract_number'],
                'project' => (string) $row['project'],
                'site' => (string) $row['site'],
                'asset_id' => (string) $row['asset_id'],
                'asset_code' => (string) $row['asset_code'],
                'start_date' => (string) $row['start_date'],
                'end_date' => (string) $row['end_date'],
                'target_hours' => $target,
                'notes' => (string) ($row['notes'] ?? ''),
                'actual_hours' => $actual,
                'achievement_percent' => $target > 0 ? round($actual / $target * 100, 2) : 0,
            ];
        }, $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public static function export(array $rows): string
    {
        $stream = fopen('php://temp', 'w+');
        if ($stream === false) throw new RuntimeException('Gagal membuat CSV.');
        fwrite($stream, "\xEF\xBB\xBF");
        $headers = array_merge(self::HEADERS, ['actual_hours', 'achievement_percent']);
        fputcsv($stream, $headers, ',', '"', '');
        foreach ($rows as $row) {
            $values = array_map(static function (string $key) use ($row): string {
                $value = (string) ($row[$key] ?? '');
                return preg_match('/^\s*[=+@\-]/u', $value) ? "'" . $value : $value;
            }, $headers);
            fputcsv($stream, $values, ',', '"', '');
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        return (string) $csv;
    }
}
