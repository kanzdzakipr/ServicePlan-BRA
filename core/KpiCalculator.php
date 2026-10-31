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

        $numPa = $pa !== null
            ? sprintf('((%.2f jam terjadwal - %.2f jam breakdown) / %.2f jam) × 100 = %.2f%%', $scheduled, $repair, $scheduled, $pa)
            : 'Tidak ada data (jam terjadwal = 0)';
        $numMa = $ma !== null
            ? sprintf('(%.2f jam operasi / (%.2f jam operasi + %.2f jam breakdown)) × 100 = %.2f%%', $working, $working, $repair, $ma)
            : 'Tidak ada data (jam operasi + breakdown = 0)';
        $numUa = $ua !== null
            ? sprintf('(%.2f jam operasi / (%.2f jam operasi + %.2f jam standby)) × 100 = %.2f%%', $working, $working, $standby, $ua)
            : 'Tidak ada data (jam operasi + standby = 0)';
        $numEu = $eu !== null
            ? sprintf('(%.2f jam operasi / %.2f jam terjadwal) × 100 = %.2f%%', $working, $scheduled, $eu)
            : 'Tidak ada data (jam terjadwal = 0)';
        $numMtbf = $mtbf !== null
            ? sprintf('%.2f jam operasi / %d kali kerusakan = %.2f jam', $working, $failures, $mtbf)
            : 'Tidak ada data (frekuensi kerusakan = 0)';
        $numMttr = $mttr !== null
            ? sprintf('%.2f jam breakdown / %d perbaikan selesai = %.2f jam', $repair, $repairs, $mttr)
            : 'Tidak ada data (perbaikan selesai = 0)';

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
                    '(Scheduled Hours – Breakdown Hours) / Scheduled Hours × 100%',
                    $numPa
                ),
                'mechanicalAvailability' => self::metric(
                    'Mechanical Availability (MA)',
                    $ma,
                    '%',
                    '(Available Hours – Mechanical Downtime) / Available Hours × 100%',
                    $numMa
                ),
                'useOfAvailability' => self::metric(
                    'Use of Availability (UA)',
                    $ua,
                    '%',
                    'Working Hours / Available Hours × 100%',
                    $numUa
                ),
                'effectiveUtilization' => self::metric(
                    'Effective Utilization (EU)',
                    $eu,
                    '%',
                    'Working Hours / Scheduled Hours × 100%',
                    $numEu
                ),
                'mtbf' => self::metric(
                    'Mean Time Between Failures (MTBF)',
                    $mtbf,
                    'jam',
                    'Operating Hours / Number of Failures',
                    $numMtbf
                ),
                'mttr' => self::metric(
                    'Mean Time To Repair (MTTR)',
                    $mttr,
                    'jam',
                    'Total Corrective Repair Hours / Number of Completed Repairs',
                    $numMttr
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
            'PA' => '(Scheduled Hours – Breakdown Hours) / Scheduled Hours × 100%',
            'MA' => '(Available Hours – Mechanical Downtime) / Available Hours × 100%',
            'UA' => 'Working Hours / Available Hours × 100%',
            'EU' => 'Working Hours / Scheduled Hours × 100%',
            'MTBF' => 'Operating Hours / Number of Failures',
            'MTTR' => 'Total Corrective Repair Hours / Number of Completed Repairs',
        ];
    }

    /** @return array{0:string,1:string} */
    public static function periodBoundaries(string $period): array
    {
        $months = [
            'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
            'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
            'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
            'january' => 1, 'february' => 2, 'march' => 3, 'may' => 5,
            'june' => 6, 'july' => 7, 'august' => 8, 'october' => 10,
            'december' => 12,
        ];

        if (!preg_match('/^([\p{L}]+)\s+(\d{4})$/u', trim($period), $matches)) {
            throw new InvalidArgumentException('Format periode harus seperti "Januari 2026".');
        }
        $month = $months[mb_strtolower($matches[1], 'UTF-8')] ?? null;
        $year = (int) $matches[2];
        if ($month === null || $year < 2000 || $year > 2100) {
            throw new InvalidArgumentException('Nama bulan atau tahun periode KPI tidak valid.');
        }

        $start = new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month));
        return [$start->format('Y-m-d H:i:s'), $start->modify('+1 month')->format('Y-m-d H:i:s')];
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
    private static function metric(string $label, ?float $value, string $unit, string $formula, string $numericFormula = ''): array
    {
        return [
            'label' => $label,
            'value' => $value,
            'formatted' => $value === null
                ? 'Tidak Ada Data'
                : number_format($value, self::PRECISION, ',', '.') . ' ' . $unit,
            'unit' => $unit,
            'formula' => $formula,
            'numericFormula' => $numericFormula !== '' ? $numericFormula : ($value === null ? 'Tidak ada data' : $formula),
            'status' => $value === null ? 'NO_DATA' : 'CALCULATED',
        ];
    }
}
