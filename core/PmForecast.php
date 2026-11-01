<?php
declare(strict_types=1);

final class PmForecast
{
    public const ADJUSTMENT_TYPES = ['METER_CORRECTION', 'LONG_STANDBY', 'BREAKDOWN', 'OPERATION_CHANGE', 'OTHER'];

    public static function estimate(string $meterDate, float $currentMeter, float $targetMeter, float $dailyUsage): ?string
    {
        if ($dailyUsage <= 0 || $targetMeter < $currentMeter) return null;
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $meterDate);
        if (!$date) throw new InvalidArgumentException('Tanggal meter tidak valid.');
        $days = (int) ceil(($targetMeter - $currentMeter) / $dailyUsage);
        return $date->modify("+{$days} days")->format('Y-m-d');
    }

    public static function adjustmentType(string $type): string
    {
        $type = strtoupper(trim($type));
        if (!in_array($type, self::ADJUSTMENT_TYPES, true)) throw new InvalidArgumentException('Jenis penyesuaian forecast tidak valid.');
        return $type;
    }

    public static function requireReason(string $reason): string
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < 5 || mb_strlen($reason) > 500) {
            throw new InvalidArgumentException('Alasan penyesuaian wajib diisi 5-500 karakter.');
        }
        return $reason;
    }

    public static function qualityFlags(mixed $meter, mixed $interval, mixed $dailyUsage): array
    {
        $flags = [];
        if ($meter === null || $meter === '' || !is_numeric($meter)) $flags[] = 'MISSING_METER';
        if ($interval === null || !is_numeric($interval) || (float) $interval <= 0) $flags[] = 'MISSING_INTERVAL';
        if ($dailyUsage === null || !is_numeric($dailyUsage) || (float) $dailyUsage <= 0) $flags[] = 'MISSING_DAILY_USAGE';
        return $flags;
    }
}
