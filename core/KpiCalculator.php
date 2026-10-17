<?php
declare(strict_types=1);

/**
 * Formula engine KPI produktivitas alat berat (GLB-D).
 *
 * Definisi variabel:
 * - W: operating hours
 * - S: standby hours
 * - R: breakdown/repair hours
 * - T: total scheduled hours
 *
 * Class ini sengaja tidak mengakses database agar satu formula yang sama dapat
 * dipakai oleh API, laporan, dan pengujian tanpa bergantung pada presentation layer.
 */
final class KpiCalculator
{
    private const PRECISION = 2;
    private const CONSISTENCY_TOLERANCE = 0.01;

    /**
     * @param array{
     *   operatingHours:int|float,
     *   standbyHours:int|float,
     *   repairHours:int|float,
     *   scheduledHours:int|float,
     *   failureCount:int|float,
     *   repairCount:int|float
     * } $input
     */
    public static function calculate(array $input): array
    {
        $working = self::nonNegativeNumber($input, 'operatingHours');
        $standby = self::nonNegativeNumber($input, 'standbyHours');
        $repair = self::nonNegativeNumber($input, 'repairHours');
        $scheduled = self::nonNegativeNumber($input, 'scheduledHours');
        $failures = self::nonNegativeInteger($input, 'failureCount');
        $repairs = self::nonNegativeInteger($input, 'repairCount');

        $componentTotal = $working + $standby + $repair;
        $difference = $scheduled - $componentTotal;
        $warnings = [];

        if ($scheduled > 0.0 && abs($difference) > self::CONSISTENCY_TOLERANCE) {
            $warnings[] = sprintf(
                'Komponen jam W + S + R berbeda %.2f jam dari total scheduled T.',
                abs($difference)
            );
        }
        if ($scheduled === 0.0) {
            $warnings[] = 'Total scheduled hours (T) nol; KPI berbasis T tidak dapat dihitung.';
        }
        if ($failures === 0) {
            $warnings[] = 'Frekuensi kerusakan nol; MTBF ditampilkan sebagai tidak ada data.';
        }
        if ($repairs === 0) {
            $warnings[] = 'Frekuensi perbaikan selesai nol; MTTR ditampilkan sebagai tidak ada data.';
        }

        $pa = self::percent($working + $standby, $scheduled);
        $ma = self::percent($working, $working + $repair);
        $ua = self::percent($working, $working + $standby);
        $eu = self::percent($working, $scheduled);
        $mtbf = self::divide($working, (float) $failures);
        $mttr = self::divide($repair, (float) $repairs);

        return [
            'variables' => [
                'W' => self::round($working),
                'S' => self::round($standby),
                'R' => self::round($repair),
                'T' => self::round($scheduled),
                'failureCount' => $failures,
                'repairCount' => $repairs,
            ],
            'kpis' => [
                'physicalAvailability' => self::metric(
                    'Physical Availability (PA)',
                    $pa,
                    '%',
                    'PA = ((W + S) / T) x 100%'
                ),
                'mechanicalAvailability' => self::metric(
                    'Mechanical Availability (MA)',
                    $ma,
                    '%',
                    'MA = (W / (W + R)) x 100%'
                ),
                'useOfAvailability' => self::metric(
                    'Use of Availability (UA)',
                    $ua,
                    '%',
                    'UA = (W / (W + S)) x 100%'
                ),
                'effectiveUtilization' => self::metric(
                    'Effective Utilization (EU)',
                    $eu,
                    '%',
                    'EU = (W / T) x 100%'
                ),
                'mtbf' => self::metric(
                    'Mean Time Between Failures (MTBF)',
                    $mtbf,
                    'jam',
                    'MTBF = W / frekuensi kerusakan'
                ),
                'mttr' => self::metric(
                    'Mean Time To Repair (MTTR)',
                    $mttr,
                    'jam',
                    'MTTR = R / frekuensi perbaikan selesai'
                ),
            ],
            'dataQuality' => [
                'isConsistent' => $warnings === [],
                'componentTotalHours' => self::round($componentTotal),
                'scheduledDifferenceHours' => self::round($difference),
                'warnings' => $warnings,
            ],
            'formulaVersion' => 'GLB-D-2026-10-17',
        ];
    }

    /** @return array<string, string> */
    public static function formulaCatalog(): array
    {
        return [
            'PA' => '((W + S) / T) x 100%',
            'MA' => '(W / (W + R)) x 100%',
            'UA' => '(W / (W + S)) x 100%',
            'EU' => '(W / T) x 100%',
            'MTBF' => 'W / frekuensi kerusakan',
            'MTTR' => 'R / frekuensi perbaikan selesai',
        ];
    }

    private static function nonNegativeNumber(array $input, string $key): float
    {
        if (!array_key_exists($key, $input) || !is_numeric($input[$key])) {
            throw new InvalidArgumentException("{$key} wajib berupa angka.");
        }

        $value = (float) $input[$key];
        if (!is_finite($value) || $value < 0.0) {
            throw new InvalidArgumentException("{$key} wajib bernilai nol atau positif.");
        }

        return $value;
    }

    private static function nonNegativeInteger(array $input, string $key): int
    {
        $value = self::nonNegativeNumber($input, $key);
        if (floor($value) !== $value) {
            throw new InvalidArgumentException("{$key} wajib berupa bilangan bulat.");
        }

        return (int) $value;
    }

    private static function percent(float $numerator, float $denominator): ?float
    {
        return $denominator <= 0.0
            ? null
            : self::round(($numerator / $denominator) * 100.0);
    }

    private static function divide(float $numerator, float $denominator): ?float
    {
        return $denominator <= 0.0 ? null : self::round($numerator / $denominator);
    }

    private static function round(float $value): float
    {
        return round($value, self::PRECISION);
    }

    /** @return array{label:string,value:?float,formatted:string,unit:string,formula:string,status:string} */
    private static function metric(string $label, ?float $value, string $unit, string $formula): array
    {
        return [
            'label' => $label,
            'value' => $value,
            'formatted' => $value === null
                ? 'Tidak Ada Data'
                : number_format($value, self::PRECISION, ',', '.') . ' ' . $unit,
            'unit' => $unit,
            'formula' => $formula,
            'status' => $value === null ? 'NO_DATA' : 'CALCULATED',
        ];
    }
}
