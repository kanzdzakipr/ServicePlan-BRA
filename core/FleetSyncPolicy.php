<?php
declare(strict_types=1);

final class FleetSyncPolicy
{
    /** Return null when the payload is a bounded, targeted sync. */
    public static function error(array $data): ?string
    {
        if (($data['mode'] ?? null) !== 'targeted') {
            return 'Sinkronisasi massal tidak diizinkan; tentukan satu objek yang berubah.';
        }
        $assets = $data['assets'] ?? [];
        $workOrders = $data['work_orders'] ?? [];
        if (!is_array($assets) || !is_array($workOrders)
            || !array_is_list($assets) || !array_is_list($workOrders)
            || count($assets) > 1 || count($workOrders) > 1
            || ($assets === [] && $workOrders === [])) {
            return 'Satu permintaan hanya boleh memuat satu aset dan satu Work Order terkait.';
        }
        if ($assets !== []) {
            $asset = $assets[0];
            if (!is_array($asset) || trim((string) ($asset['id'] ?? '')) === ''
                || !in_array($asset['status'] ?? null, ['READY', 'OPERATING', 'STANDBY', 'INSPEKSI', 'BREAKDOWN', 'ACCIDENT_HOLD', 'INACTIVE'], true)
                || !in_array($asset['expected_status'] ?? null, ['READY', 'OPERATING', 'STANDBY', 'INSPEKSI', 'BREAKDOWN', 'ACCIDENT_HOLD', 'INACTIVE'], true)
                || (isset($asset['last_hm_km']) && (!is_numeric($asset['last_hm_km']) || (float) $asset['last_hm_km'] < 0))) {
                return 'Data aset tidak valid.';
            }
        }
        if ($workOrders !== []) {
            $workOrder = $workOrders[0];
            if (!is_array($workOrder) || trim((string) ($workOrder['woId'] ?? '')) === ''
                || trim((string) ($workOrder['assetId'] ?? '')) === ''
                || !in_array($workOrder['status'] ?? null, ['Open', 'In Progress', 'Waiting Part', 'Testing', 'Closed', 'Cancelled'], true)) {
                return 'Data Work Order tidak valid.';
            }
            if ($assets !== [] && (string) $workOrder['assetId'] !== (string) $assets[0]['id']) {
                return 'Work Order harus terkait dengan aset pada permintaan yang sama.';
            }
        }
        return null;
    }
}
