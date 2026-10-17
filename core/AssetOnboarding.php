<?php
declare(strict_types=1);

final class AssetOnboarding
{
    public static function history($items): array
    {
        if ($items === null || $items === '') return [];
        if (!is_array($items) || count($items) > 30) throw new InvalidArgumentException('Riwayat servis maksimal 30 baris.');
        $normalized = [];
        foreach ($items as $item) {
            if (!is_array($item)) throw new InvalidArgumentException('Format riwayat servis tidak valid.');
            $date = self::date($item['date'] ?? null);
            $meter = self::nonNegative($item['meter'] ?? null, 'Meter riwayat servis');
            $type = trim((string) ($item['type'] ?? ''));
            if ($date === null || $meter === null || $type === '' || mb_strlen($type, 'UTF-8') > 100) {
                throw new InvalidArgumentException('Setiap riwayat servis memerlukan tanggal, meter, dan jenis servis.');
            }
            $normalized[] = ['date' => $date . ' 00:00:00', 'meter' => $meter, 'type' => $type];
        }
        usort($normalized, static fn(array $a, array $b): int => strcmp($a['date'], $b['date']));
        return $normalized;
    }

    public static function date(?string $value, bool $withTime = false): ?string
    {
        $value = trim((string) $value);
        if ($value === '') return null;
        $formats = $withTime ? ['Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d'] : ['Y-m-d'];
        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
            if ($date && $date->format($format) === $value) {
                return $date->format($withTime ? 'Y-m-d H:i:s' : 'Y-m-d');
            }
        }
        throw new InvalidArgumentException('Format tanggal tidak valid.');
    }

    public static function nonNegative($value, string $label, bool $positive = false): ?float
    {
        if ($value === null || $value === '') return null;
        if (!is_numeric($value)) throw new InvalidArgumentException("$label harus berupa angka.");
        $number = (float) $value;
        if (!is_finite($number) || ($positive ? $number <= 0 : $number < 0)) {
            throw new InvalidArgumentException("$label harus " . ($positive ? 'lebih besar dari nol.' : 'nol atau lebih.'));
        }
        return $number;
    }

    public static function fields(array $data, float $currentMeter): array
    {
        $operator = trim((string) ($data['current_operator_name'] ?? ''));
        if (mb_strlen($operator, 'UTF-8') > 150) throw new InvalidArgumentException('Nama operator terlalu panjang.');
        $lastDate = self::date($data['last_service_date'] ?? null, true);
        $lastMeter = self::nonNegative($data['last_service_hm_km'] ?? null, 'Meter terakhir servis');
        $intervalRaw = self::nonNegative($data['service_interval_val'] ?? null, 'Interval servis', true);
        if ($intervalRaw !== null && ($intervalRaw != floor($intervalRaw) || $intervalRaw > 1000000)) throw new InvalidArgumentException('Interval servis tidak valid.');
        $interval = $intervalRaw !== null ? (int) $intervalRaw : null;
        $unit = trim((string) ($data['service_interval_unit'] ?? ''));
        if ($unit !== '' && !in_array($unit, ['HM', 'KM'], true)) throw new InvalidArgumentException('Jenis meter harus HM atau KM.');
        $coefficient = self::nonNegative($data['daily_usage_coefficient'] ?? null, 'Pemakaian per hari', true);
        if ($coefficient !== null && $coefficient > 100000) throw new InvalidArgumentException('Pemakaian per hari terlalu besar.');
        if (($lastDate !== null || $lastMeter !== null || $interval !== null || $coefficient !== null)
            && ($lastDate === null || $lastMeter === null || $interval === null || $unit === '' || $coefficient === null)) {
            throw new InvalidArgumentException('Tanggal dan meter servis terakhir, interval, jenis meter, serta pemakaian harian harus diisi bersama.');
        }
        $target = $lastMeter !== null ? $lastMeter + $interval : null;
        $estimate = null;
        if ($target !== null) {
            $days = max(0, (int) ceil(($target - $currentMeter) / $coefficient));
            $estimate = (new DateTimeImmutable('today'))->modify("+$days days")->format('Y-m-d');
        }
        return [
            'current_operator_name' => $operator !== '' ? $operator : null,
            'last_service_date' => $lastDate,
            'last_service_hm_km' => $lastMeter,
            'service_interval_val' => $interval,
            'service_interval_unit' => $unit !== '' ? $unit : null,
            'daily_usage_coefficient' => $coefficient,
            'next_service_due_hm_km' => $target,
            'next_service_estimated_date' => $estimate,
            'legal_stnk_expiry' => self::date($data['legal_stnk_expiry'] ?? null),
            'legal_kir_expiry' => self::date($data['legal_kir_expiry'] ?? null),
            'legal_silo_expiry' => self::date($data['legal_silo_expiry'] ?? null),
        ];
    }
}
