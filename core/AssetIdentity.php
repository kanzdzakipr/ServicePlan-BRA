<?php
declare(strict_types=1);

/**
 * Standard Asset Identity and Completeness Evaluator
 * Part of Revision 1 (Master Asset - BR-01 / M3)
 */

if (!defined('ASSET_CATEGORIES')) {
    define('ASSET_CATEGORIES', [
        'Excavator', 'Bulldozer', 'Dump Truck', 'Motor Grader',
        'Vibro Compactor', 'Water Truck', 'Prime Mover', 'Light Vehicle',
        'Reclaimer Spreader', 'Trado', 'Other'
    ]);
}

if (!defined('ASSET_STATUSES')) {
    define('ASSET_STATUSES', [
        'READY', 'OPERATING', 'STANDBY', 'INSPEKSI', 'BREAKDOWN', 'ACCIDENT_HOLD', 'INACTIVE'
    ]);
}

if (!function_exists('asset_normalize_status')) {
    function asset_normalize_status(string $status): string
    {
        $clean = strtoupper(trim($status));
        if ($clean === 'ACCIDENT HOLD') {
            return 'ACCIDENT_HOLD';
        }
        return $clean;
    }
}

if (!function_exists('asset_compute_completeness')) {
    function asset_compute_completeness(array $row): array
    {
        $missing = [];
        $sn = trim((string) ($row['serial_number'] ?? ''));
        $plate = trim((string) ($row['license_plate'] ?? ''));
        $model = trim((string) ($row['make_model'] ?? ''));
        $year = $row['year_manufacture'] ?? null;
        $loc = $row['current_location_id'] ?? null;

        if ($sn === '' && $plate === '') {
            $missing[] = 'SN / Nomor Pelat';
        } elseif ($sn === '') {
            $missing[] = 'Nomor Seri (SN)';
        } elseif ($plate === '' && in_array($row['category'] ?? '', ['Dump Truck', 'Light Vehicle', 'Water Truck', 'Prime Mover', 'Trado'], true)) {
            $missing[] = 'Nomor Pelat';
        }

        if ($model === '') {
            $missing[] = 'Merek / Model';
        }

        if ($year === null || (int) $year < 1970) {
            $missing[] = 'Tahun Pembuatan';
        }

        if ($loc === null || (int) $loc < 1) {
            $missing[] = 'Lokasi Aktif';
        }

        return [
            'is_complete' => empty($missing),
            'missing_fields' => $missing,
            'completeness_label' => empty($missing) ? 'Lengkap' : 'Belum Lengkap',
        ];
    }
}
