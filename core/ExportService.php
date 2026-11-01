<?php
declare(strict_types=1);

final class ExportService
{
    public static function sanitizeCell(mixed $value): string
    {
        if ($value === null) return '';
        if (is_bool($value)) return $value ? 'Ya' : 'Tidak';
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $text = str_replace(["\r\n", "\r"], "\n", (string) $value);
        // Prevent spreadsheet formula injection while preserving the visible value.
        if (preg_match('/^[\x00-\x20]*[=+\-@]/u', $text) === 1) $text = "'" . $text;
        return $text;
    }

    public static function filename(string $module, ?string $period = null, ?string $location = null): string
    {
        $parts = [$module, $period ?: 'semua-periode', $location ?: 'semua-lokasi', date('Ymd-His')];
        $safe = array_map(static function (string $part): string {
            $part = strtolower(trim($part));
            $part = preg_replace('/[^a-z0-9]+/i', '-', $part) ?: 'data';
            return trim($part, '-');
        }, $parts);
        return implode('_', $safe) . '.csv';
    }

    /** @param list<array<string,mixed>> $rows */
    public static function csv(array $rows, ?array $columns = null): string
    {
        if ($columns === null) $columns = $rows ? array_keys($rows[0]) : [];
        $stream = fopen('php://temp', 'w+');
        if ($stream === false) throw new RuntimeException('Buffer ekspor tidak tersedia.');
        fwrite($stream, "\xEF\xBB\xBF");
        if ($columns) fputcsv($stream, array_map([self::class, 'sanitizeCell'], $columns));
        foreach ($rows as $row) {
            $values = [];
            foreach ($columns as $column) $values[] = self::sanitizeCell($row[$column] ?? null);
            fputcsv($stream, $values);
        }
        rewind($stream);
        $result = stream_get_contents($stream);
        fclose($stream);
        return $result === false ? '' : $result;
    }

    /** @param list<array<string,mixed>> $rows */
    public static function download(array $rows, string $module, ?string $period = null, ?string $location = null, ?array $columns = null): never
    {
        $filename = self::filename($module, $period, $location);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        echo self::csv($rows, $columns);
        exit;
    }
}
