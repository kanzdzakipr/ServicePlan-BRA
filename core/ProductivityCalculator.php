<?php
declare(strict_types=1);

require_once __DIR__ . '/KpiCalculator.php';

/**
 * Core Service: Productivity & Telematics Calculator (BR-07 / M8)
 * 
 * Computes derived equipment KPIs (Physical Availability, Use of Availability,
 * Breakdown Rate, Utilization Rate, MTBF, MTTR, Fuel & Idling metrics)
 * based on KOMTRAX telematics logs, work orders, and LHO operation reports.
 * 
 * Formulations strictly adhere to PT BRA Standard Equipment Reliability:
 * - PA   = ((Operating Hours + Standby Hours) / Scheduled Hours) * 100% [Target: >= 90.0%]
 * - MA   = (Operating Hours / (Operating Hours + Repair Hours)) * 100%
 * - UA   = (Operating Hours / (Operating Hours + Standby Hours)) * 100% [Target: >= 80.0%]
 * - BR   = (Downtime Hours / Scheduled Hours) * 100% [Target: <= 10.0%]
 * - UT   = (Operating Hours / Scheduled Hours) * 100%
 * - MTBF = Total Operating Hours / Breakdown Count [Target: >= 100h]
 * - MTTR = Total Downtime Hours / Closed Repair Count [Target: <= 4.0h]
 */
final class ProductivityCalculator
{
    public const FUEL_COST_PER_LITER = 14500; // Rp 14,500/L solar industri (default fallback)

    /**
     * Get reference industrial fuel price per liter from operational_settings (default: 14500).
     */
    public static function getFuelPricePerLiter(?PDO $db = null): float
    {
        if ($db === null) {
            $db = Database::getInstance();
        }
        try {
            $stmt = $db->query("SELECT setting_value FROM operational_settings WHERE setting_key = 'cfg_fuel_price_per_liter' LIMIT 1");
            $val = $stmt ? $stmt->fetchColumn() : false;
            if ($val !== false && is_numeric($val) && (float) $val > 0) {
                return (float) $val;
            }
        } catch (Throwable $t) {
            // Graceful fallback if operational_settings is unavailable
        }
        return (float) self::FUEL_COST_PER_LITER;
    }

    /**
     * Compute comprehensive productivity, telematics reconciliation, and standby fleet data.
     */
    public static function calculate(PDO $db, ?int $locationId = null, ?string $period = 'Januari 2026', ?string $assetId = null, ?string $category = null): array
    {
        $fuelCostPerLiter = self::getFuelPricePerLiter($db);

        // 1. Fetch telematics records with location scoping
        $sqlTele = "SELECT 
                        t.*,
                        a.asset_code,
                        a.category AS asset_category,
                        a.current_location_id,
                        l.location_name
                    FROM telematics_logs t
                    LEFT JOIN assets a ON (t.asset_id = a.asset_id OR (t.asset_id IS NULL AND t.serial_number = a.serial_number))
                    LEFT JOIN locations l ON a.current_location_id = l.location_id
                    WHERE 1=1";
        $paramsTele = [];

        if ($period !== null && $period !== '' && $period !== 'all') {
            $sqlTele .= " AND t.period_month = :period";
            $paramsTele[':period'] = $period;
        }

        if ($locationId !== null) {
            $sqlTele .= " AND a.current_location_id = :location_id";
            $paramsTele[':location_id'] = $locationId;
        }
        if ($assetId !== null) {
            $sqlTele .= ' AND a.asset_id = :tele_asset_id';
            $paramsTele[':tele_asset_id'] = $assetId;
        }
        if ($category !== null) {
            $sqlTele .= ' AND a.category = :tele_category';
            $paramsTele[':tele_category'] = $category;
        }

        $sqlTele .= " ORDER BY t.log_id ASC";
        $stmtTele = $db->prepare($sqlTele);
        $stmtTele->execute($paramsTele);
        $teleRows = $stmtTele->fetchAll(PDO::FETCH_ASSOC);

        $recordCount = count($teleRows);

        // 2. Fetch manual LHO operations aggregated by asset for reconciliation
        $sqlLho = "SELECT 
                       o.asset_id,
                       SUM(o.work_hours) as total_lho_work_hours,
                       SUM(o.hm_operation) as total_lho_hm_operation,
                       SUM(o.fuel_liters) as total_lho_fuel,
                       COUNT(*) as lho_record_count
                   FROM report_operation_logs o
                   INNER JOIN assets a ON a.asset_id = o.asset_id
                   WHERE o.reversed_at IS NULL";
        $lhoParams = [];
        if ($period !== null && $period !== '' && $period !== 'all') {
            [$periodStart, $periodEnd] = KpiCalculator::periodBoundaries($period);
            $sqlLho .= ' AND o.operation_date >= :lho_start AND o.operation_date < :lho_end';
            $lhoParams[':lho_start'] = substr($periodStart, 0, 10);
            $lhoParams[':lho_end'] = substr($periodEnd, 0, 10);
        }
        if ($locationId !== null) {
            $sqlLho .= ' AND a.current_location_id = :lho_location_id';
            $lhoParams[':lho_location_id'] = $locationId;
        }
        if ($assetId !== null) { $sqlLho .= ' AND a.asset_id = :lho_asset_id'; $lhoParams[':lho_asset_id'] = $assetId; }
        if ($category !== null) { $sqlLho .= ' AND a.category = :lho_category'; $lhoParams[':lho_category'] = $category; }
        $sqlLho .= ' GROUP BY o.asset_id';
        $lhoStmt = $db->prepare($sqlLho);
        $lhoStmt->execute($lhoParams);
        $lhoAgg = $lhoStmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

        // 3. If no telematics data exists for this scope/filter, return clean NO-DATA indicators
        if ($recordCount === 0) {
            return [
                'kpis' => self::buildNoDataKpis(),
                'telematics' => [],
                'idlingAnomalies' => [],
                'reconciliation' => [],
                'standbySummary' => self::getStandbySummary($db, $locationId),
                'meta' => [
                    'period' => $period,
                    'locationId' => $locationId,
                    'recordCount' => 0,
                    'status' => 'NO_DATA',
                    'message' => 'Tidak ada data telematika pada filter lokasi/periode ini.',
                ]
            ];
        }

        // 4. Aggregate Telematics Metrics
        $totalWorkingHours = 0.0;
        $totalActualHours = 0.0;
        $totalFuelConsumed = 0.0;
        $activeUnitsForIdling = 0;
        $sumIdlingRatio = 0.0;

        $telematicsFormatted = [];
        $idlingAnomalies = [];
        $reconciliationRows = [];

        $now = time();

        foreach ($teleRows as $idx => $r) {
            $wh = (float) $r['working_hours'];
            $actWh = (float) $r['actual_working_hours'];
            $fuel = (float) $r['fuel_consumed_liters'];
            $fuelLph = (float) $r['fuel_lph'];
            $idling = (float) $r['idling_ratio'];
            $smr = (float) $r['smr_hours'];
            $sn = (string) $r['serial_number'];
            $unitAssetId = $r['asset_id'] ?? null;
            $assetCode = $r['asset_code'] ?? ($unitAssetId ?? '-');

            $totalWorkingHours += $wh;
            $totalActualHours += $actWh;
            $totalFuelConsumed += $fuel;

            if ($wh > 0) {
                $activeUnitsForIdling++;
                $sumIdlingRatio += $idling;
            }

            // Derive individual unit status
            $status = 'Active';
            if ($idling >= 60.0) {
                $status = 'Critical Idling';
            } elseif ($idling >= 50.0) {
                $status = 'High Idling';
            } elseif ($wh == 0.0 && $smr > 0) {
                $lastCommTime = strtotime((string) $r['last_comm_date']);
                if ($lastCommTime && ($now - $lastCommTime) > 30 * 86400) {
                    $status = 'Comm Offline';
                } else {
                    $status = 'No Activity';
                }
            } elseif ($wh > 0 && ((float) $r['actual_working_ratio']) >= 65.0) {
                $status = 'Top Workload';
            } elseif ($wh > 0 && ((float) $r['actual_working_ratio']) >= 60.0) {
                $status = 'High Efficiency';
            }

            $teleRowFormatted = [
                'no' => $idx + 1,
                'assetId' => $unitAssetId,
                'assetCode' => $assetCode,
                'model' => (string) $r['model_type'],
                'sn' => $sn,
                'branch' => (string) $r['sub_group'],
                'locationName' => $r['location_name'] ?? null,
                'smr' => $smr,
                'lastComm' => (string) $r['last_comm_date'],
                'days' => (int) $r['working_days'],
                'hours' => $wh,
                'actualHours' => $actWh,
                'actualRatio' => (float) $r['actual_working_ratio'],
                'eModeRatio' => (float) $r['e_mode_ratio'],
                'travelRatio' => (float) $r['travel_ratio'],
                'diggingRatio' => (float) $r['digging_ratio'],
                'hoistRatio' => (float) $r['hoist_ratio'],
                'fuelLiters' => $fuel,
                'fuelLPH' => $fuelLph,
                'idlingRatio' => $idling,
                'status' => $status,
            ];
            $telematicsFormatted[] = $teleRowFormatted;

            // Idling Anomaly Detection (> 50%)
            if ($idling >= 50.0) {
                $estimatedIdleFuel = round($fuel * ($idling / 100.0), 1);
                $estimatedLossCost = round($estimatedIdleFuel * $fuelCostPerLiter);
                $idlingAnomalies[] = [
                    'sn' => $sn,
                    'model' => (string) $r['model_type'],
                    'branch' => (string) $r['sub_group'],
                    'hours' => $wh,
                    'idlingRatio' => $idling,
                    'estimatedIdleFuel' => $estimatedIdleFuel,
                    'estimatedLossCost' => $estimatedLossCost,
                    'fuelPricePerLiter' => $fuelCostPerLiter,
                    'recommendation' => 'Audit perilaku operator & matikan mesin saat standby > 5 menit.',
                ];
            }

            // Reconciliation against Manual LHO Operations
            $lhoInfo = ($unitAssetId && isset($lhoAgg[$unitAssetId])) ? $lhoAgg[$unitAssetId] : null;
            $manualHours = $lhoInfo ? (float) $lhoInfo['total_lho_work_hours'] : null;

            $exceptions = [];
            $varianceHours = null;
            $variancePct = null;

            if ($manualHours !== null) {
                $varianceHours = round(abs($actWh - $manualHours), 1);
                $variancePct = $actWh > 0 ? round(($varianceHours / $actWh) * 100.0, 1) : ($manualHours > 0 ? 100.0 : 0.0);
                if ($variancePct > 15.0) {
                    $exceptions[] = 'Selisih Jam Kerja Tinggi (>15%)';
                }
            } else {
                $exceptions[] = 'Belum Ada LHO Manual';
            }

            if ($idling >= 50.0) {
                $exceptions[] = 'Idling Anomaly (>50%)';
            }

            if ($status === 'Comm Offline') {
                $exceptions[] = 'Telematika Offline';
            } elseif ($status === 'No Activity') {
                $exceptions[] = 'Tidak Ada Aktivitas SMR';
            }

            $reconciliationRows[] = [
                'sn' => $sn,
                'model' => (string) $r['model_type'],
                'branch' => (string) $r['sub_group'],
                'assetCode' => $assetCode,
                'telematicsHours' => $actWh,
                'manualLhoHours' => $manualHours,
                'varianceHours' => $varianceHours,
                'variancePercent' => $variancePct,
                'exceptions' => $exceptions,
                'reconciliationStatus' => empty($exceptions) ? 'MATCH' : 'ANOMALY',
                'actionRequired' => empty($exceptions) ? 'Data Sesuai' : implode(', ', $exceptions),
            ];
        }

        // 5. Fleet KPI Calculations
        $avgFuelLPH = $totalWorkingHours > 0 ? round($totalFuelConsumed / $totalWorkingHours, 2) : 0.0;
        $avgIdlingRatio = $activeUnitsForIdling > 0 ? round($sumIdlingRatio / $activeUnitsForIdling, 2) : 0.0;

        // Pull reliability facts from Work Orders for the same period and scope.
        $repairSql = "SELECT
                COALESCE(SUM(CASE WHEN w.is_downtime = 1 AND w.status <> 'Cancelled' THEN w.downtime_minutes ELSE 0 END), 0) / 60 AS repair_hours,
                COALESCE(SUM(CASE WHEN w.is_downtime = 1 AND w.status <> 'Cancelled' THEN 1 ELSE 0 END), 0) AS failure_count,
                COALESCE(SUM(CASE WHEN w.is_downtime = 1 AND w.status = 'Closed' THEN 1 ELSE 0 END), 0) AS repair_count
            FROM work_orders w
            INNER JOIN assets a ON a.asset_id = w.asset_id
            WHERE 1=1";
        $repairParams = [];
        if ($period !== null && $period !== '' && $period !== 'all') {
            [$periodStart, $periodEnd] = KpiCalculator::periodBoundaries($period);
            $repairSql .= ' AND COALESCE(w.trouble_at, w.reported_at) >= :period_start
                            AND COALESCE(w.trouble_at, w.reported_at) < :period_end';
            $repairParams[':period_start'] = $periodStart;
            $repairParams[':period_end'] = $periodEnd;
        }
        if ($locationId !== null) {
            $repairSql .= ' AND a.current_location_id = :repair_location_id';
            $repairParams[':repair_location_id'] = $locationId;
        }
        if ($assetId !== null) {
            $repairSql .= ' AND a.asset_id = :repair_asset_id';
            $repairParams[':repair_asset_id'] = $assetId;
        } else {
            // When calculating fleet KPI without single-asset filter, scope work orders to the telematics fleet
            $teleAssetIds = array_values(array_filter(array_unique(array_column($telematicsFormatted, 'assetId'))));
            if (!empty($teleAssetIds)) {
                $placeholders = [];
                foreach ($teleAssetIds as $tIdx => $tAid) {
                    $pKey = ":tele_aid_{$tIdx}";
                    $placeholders[] = $pKey;
                    $repairParams[$pKey] = $tAid;
                }
                $repairSql .= ' AND a.asset_id IN (' . implode(',', $placeholders) . ')';
            }
        }
        if ($category !== null) { $repairSql .= ' AND a.category = :repair_category'; $repairParams[':repair_category'] = $category; }
        $repairStatement = $db->prepare($repairSql);
        $repairStatement->execute($repairParams);
        $repairFacts = $repairStatement->fetch(PDO::FETCH_ASSOC) ?: [];

        $downtimeHours = (float) ($repairFacts['repair_hours'] ?? 0);
        $breakdownCount = (int) ($repairFacts['failure_count'] ?? 0);
        $closedRepairsCount = (int) ($repairFacts['repair_count'] ?? 0);
        $standbyHours = max(0.0, $totalWorkingHours - $totalActualHours - $downtimeHours);
        $lockedKpis = KpiCalculator::calculate([
            'operatingHours' => $totalActualHours,
            'standbyHours' => $standbyHours,
            'repairHours' => $downtimeHours,
            'scheduledHours' => $totalWorkingHours,
            'failureCount' => $breakdownCount,
            'repairCount' => $closedRepairsCount,
        ]);

        $pa = $lockedKpis['kpis']['physicalAvailability']['value'];
        $ma = $lockedKpis['kpis']['mechanicalAvailability']['value'];
        $ua = $lockedKpis['kpis']['useOfAvailability']['value'];
        $ut = $lockedKpis['kpis']['effectiveUtilization']['value'];
        $mtbf = $lockedKpis['kpis']['mtbf']['value'];
        $mttr = $lockedKpis['kpis']['mttr']['value'];
        $br = $totalWorkingHours > 0 ? round(($downtimeHours / $totalWorkingHours) * 100.0, 2) : null;
        $availableHours = $totalActualHours + $standbyHours;

        $kpis = [
            'physicalAvailability' => [
                'value' => $pa,
                'formatted' => $lockedKpis['kpis']['physicalAvailability']['formatted'],
                'unit' => '%',
                'target' => '>= 90.0%',
                'status' => ($pa !== null && $pa >= 90.0) ? 'MEETS_TARGET' : 'BELOW_TARGET',
                'formula' => $lockedKpis['kpis']['physicalAvailability']['formula'],
                'sources' => ['telematics_logs.actual_working_hours', 'telematics_logs.working_hours', 'work_orders.downtime_minutes'],
                'details' => [
                    'workingHours' => round($totalWorkingHours, 1),
                    'downtimeHours' => $downtimeHours,
                ],
            ],
            'mechanicalAvailability' => [
                'value' => $ma,
                'formatted' => $lockedKpis['kpis']['mechanicalAvailability']['formatted'],
                'unit' => '%',
                'target' => 'BRA perlu persetujuan',
                'status' => 'INFORMATIONAL',
                'formula' => $lockedKpis['kpis']['mechanicalAvailability']['formula'],
                'sources' => ['telematics_logs.actual_working_hours', 'work_orders.downtime_minutes'],
                'details' => ['operatingHours' => round($totalActualHours, 2), 'repairHours' => round($downtimeHours, 2)],
            ],
            'useOfAvailability' => [
                'value' => $ua,
                'formatted' => $ua !== null ? "{$ua}%" : 'Tidak Ada Data',
                'unit' => '%',
                'target' => '>= 80.0%',
                'status' => ($ua !== null && $ua >= 80.0) ? 'MEETS_TARGET' : 'BELOW_TARGET',
                'formula' => $lockedKpis['kpis']['useOfAvailability']['formula'],
                'sources' => ['telematics_logs.actual_working_hours', 'Derived standby hours'],
                'details' => [
                    'actualHours' => round($totalActualHours, 1),
                    'availableHours' => round($availableHours, 1),
                ],
            ],
            'breakdownRate' => [
                'value' => $br,
                'formatted' => $br !== null ? "{$br}%" : 'Tidak Ada Data',
                'unit' => '%',
                'target' => '<= 10.0%',
                'status' => ($br !== null && $br <= 10.0) ? 'MEETS_TARGET' : 'ABOVE_LIMIT',
                'formula' => 'BR = (Downtime Hours / Working Hours) * 100%',
                'sources' => ['work_orders.downtime_minutes', 'telematics_logs.working_hours'],
                'details' => [
                    'downtimeHours' => $downtimeHours,
                    'workingHours' => round($totalWorkingHours, 1),
                ],
            ],
            'utilizationRate' => [
                'value' => $ut,
                'formatted' => $ut !== null ? "{$ut}%" : 'Tidak Ada Data',
                'unit' => '%',
                'target' => 'Fleet Average Baseline',
                'status' => 'INFORMATIONAL',
                'formula' => $lockedKpis['kpis']['effectiveUtilization']['formula'],
                'sources' => ['telematics_logs.actual_working_hours', 'telematics_logs.working_hours'],
                'details' => [
                    'actualHours' => round($totalActualHours, 1),
                    'workingHours' => round($totalWorkingHours, 1),
                ],
            ],
            'mtbf' => [
                'value' => $mtbf,
                'formatted' => $mtbf !== null ? "{$mtbf} Jam" : 'Tidak Ada Data',
                'unit' => 'Jam',
                'target' => '>= 100 Jam',
                'status' => ($mtbf !== null && $mtbf >= 100.0) ? 'MEETS_TARGET' : 'BELOW_TARGET',
                'formula' => $lockedKpis['kpis']['mtbf']['formula'],
                'sources' => ['telematics_logs.actual_working_hours', 'work_orders downtime occurrence count'],
                'details' => [
                    'operatingHours' => round($totalActualHours, 1),
                    'breakdownCount' => $breakdownCount,
                ],
            ],
            'mttr' => [
                'value' => $mttr,
                'formatted' => $mttr !== null ? "{$mttr} Jam" : 'Tidak Ada Data',
                'unit' => 'Jam',
                'target' => '<= 4.0 Jam',
                'status' => ($mttr !== null && $mttr <= 4.0) ? 'MEETS_TARGET' : 'ABOVE_LIMIT',
                'formula' => $lockedKpis['kpis']['mttr']['formula'],
                'sources' => ['work_orders.downtime_minutes', 'work_orders closed repair count'],
                'details' => [
                    'downtimeHours' => $downtimeHours,
                    'closedCount' => $closedRepairsCount,
                ],
            ],
            'totalWorkingHours' => [
                'value' => round($totalWorkingHours, 1),
                'formatted' => number_format(round($totalWorkingHours, 1), 1) . ' Jam',
                'unit' => 'Jam',
                'sources' => ['telematics_logs.working_hours'],
            ],
            'totalActualHours' => [
                'value' => round($totalActualHours, 1),
                'formatted' => number_format(round($totalActualHours, 1), 1) . ' Jam',
                'unit' => 'Jam',
                'sources' => ['telematics_logs.actual_working_hours'],
            ],
            'totalFuelConsumed' => [
                'value' => round($totalFuelConsumed, 1),
                'formatted' => number_format(round($totalFuelConsumed, 1), 1) . ' L',
                'unit' => 'Liter',
                'sources' => ['telematics_logs.fuel_consumed_liters'],
            ],
            'avgFuelLPH' => [
                'value' => $avgFuelLPH,
                'formatted' => "{$avgFuelLPH} L/H",
                'unit' => 'L/H',
                'sources' => ['Total Fuel Consumed / Total Scheduled Working Hours'],
            ],
            'avgIdlingRatio' => [
                'value' => $avgIdlingRatio,
                'formatted' => "{$avgIdlingRatio}%",
                'unit' => '%',
                'sources' => ['telematics_logs.idling_ratio'],
            ],
        ];

        return [
            'kpis' => $kpis,
            'telematics' => $telematicsFormatted,
            'idlingAnomalies' => $idlingAnomalies,
            'reconciliation' => $reconciliationRows,
            'standbySummary' => self::getStandbySummary($db, $locationId),
            'fuelPricePerLiter' => $fuelCostPerLiter,
            'meta' => [
                'period' => $period,
                'locationId' => $locationId,
                'assetId' => $assetId,
                'recordCount' => $recordCount,
                'status' => 'SUCCESS',
                'formulaVersion' => $lockedKpis['formulaVersion'],
                'dataQuality' => $lockedKpis['dataQuality'],
            ]
        ];
    }

    /**
     * Fallback KPI object when no data is available for a location or filter.
     * Adheres to requirement BR-07B: "Missing data tidak disamarkan sebagai nol".
     */
    private static function buildNoDataKpis(): array
    {
        $keys = [
            'physicalAvailability' => ['unit' => '%', 'target' => '>= 90.0%', 'formula' => 'PA = ((W + S) / T) x 100%'],
            'mechanicalAvailability' => ['unit' => '%', 'target' => 'BRA perlu persetujuan', 'formula' => 'MA = (W / (W + R)) x 100%'],
            'useOfAvailability'    => ['unit' => '%', 'target' => '>= 80.0%', 'formula' => 'UA = (W / (W + S)) x 100%'],
            'breakdownRate'        => ['unit' => '%', 'target' => '<= 10.0%', 'formula' => 'BR = (Downtime Hours / Working Hours) * 100%'],
            'utilizationRate'      => ['unit' => '%', 'target' => 'Fleet Baseline', 'formula' => 'EU = (W / T) x 100%'],
            'mtbf'                 => ['unit' => 'Jam', 'target' => '>= 100 Jam', 'formula' => 'MTBF = Actual Operating Hours / Breakdown Count'],
            'mttr'                 => ['unit' => 'Jam', 'target' => '<= 4.0 Jam', 'formula' => 'MTTR = Downtime Hours / Closed Repair Count'],
            'totalWorkingHours'    => ['unit' => 'Jam', 'target' => '-', 'formula' => 'Sum of working hours'],
            'totalActualHours'     => ['unit' => 'Jam', 'target' => '-', 'formula' => 'Sum of actual operating hours'],
            'totalFuelConsumed'    => ['unit' => 'Liter', 'target' => '-', 'formula' => 'Sum of fuel consumed'],
            'avgFuelLPH'           => ['unit' => 'L/H', 'target' => '-', 'formula' => 'Fuel / Actual Hours'],
            'avgIdlingRatio'       => ['unit' => '%', 'target' => '<= 30.0%', 'formula' => 'Average idling ratio'],
        ];

        $result = [];
        foreach ($keys as $k => $spec) {
            $result[$k] = [
                'value' => null,
                'formatted' => 'Tidak Ada Data',
                'unit' => $spec['unit'],
                'target' => $spec['target'],
                'status' => 'NO_DATA',
                'formula' => $spec['formula'],
                'sources' => ['Data belum tersedia untuk cakupan lokasi/periode ini'],
                'details' => null,
            ];
        }
        return $result;
    }

    /**
     * Standby fleet audit summary dynamically aggregated from database assets & locations.
     */
    public static function getStandbySummary(?PDO $db = null, ?int $locationId = null): array
    {
        if ($db === null) {
            $db = Database::getInstance();
        }

        $where = "a.status = 'STANDBY'";
        $params = [];
        if ($locationId !== null) {
            $where .= ' AND a.current_location_id = :sb_loc_id';
            $params[':sb_loc_id'] = $locationId;
        }

        // 1. Total standby units
        $stmtTotal = $db->prepare("SELECT COUNT(*) FROM assets a WHERE {$where}");
        $stmtTotal->execute($params);
        $totalUnits = (int) $stmtTotal->fetchColumn();

        // 2. Locations breakdown
        $sqlLoc = "SELECT 
                       COALESCE(l.location_name, 'Lokasi Lainnya') AS name,
                       COUNT(*) AS count,
                       SUM(CASE WHEN a.category LIKE '%Dump Truck%' THEN 1 ELSE 0 END) AS dumpTrucks,
                       SUM(CASE WHEN a.category LIKE '%Excavator%' THEN 1 ELSE 0 END) AS excavators,
                       SUM(CASE WHEN a.category LIKE '%Compactor%' THEN 1 ELSE 0 END) AS compactors,
                       SUM(CASE WHEN a.category LIKE '%Bulldozer%' THEN 1 ELSE 0 END) AS bulldozers,
                       SUM(CASE WHEN a.category LIKE '%Trado%' THEN 1 ELSE 0 END) AS trados
                   FROM assets a
                   LEFT JOIN locations l ON a.current_location_id = l.location_id
                   WHERE {$where}
                   GROUP BY COALESCE(l.location_name, 'Lokasi Lainnya')
                   ORDER BY count DESC";
        $stmtLoc = $db->prepare($sqlLoc);
        $stmtLoc->execute($params);
        $locRows = $stmtLoc->fetchAll(PDO::FETCH_ASSOC);

        $locations = [];
        foreach ($locRows as $lr) {
            $c = (int) $lr['count'];
            $locations[] = [
                'name' => strtoupper((string) $lr['name']),
                'count' => $c,
                'ratio' => $totalUnits > 0 ? round(($c / $totalUnits) * 100.0, 1) : 0.0,
                'dumpTrucks' => (int) $lr['dumpTrucks'],
                'excavators' => (int) $lr['excavators'],
                'compactors' => (int) $lr['compactors'],
                'bulldozers' => (int) $lr['bulldozers'],
                'trados' => (int) $lr['trados'],
            ];
        }

        // 3. Categories breakdown
        $sqlCat = "SELECT 
                       COALESCE(a.category, 'Lainnya') AS type,
                       COUNT(*) AS count
                   FROM assets a
                   WHERE {$where}
                   GROUP BY COALESCE(a.category, 'Lainnya')
                   ORDER BY count DESC";
        $stmtCat = $db->prepare($sqlCat);
        $stmtCat->execute($params);
        $catRows = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

        $impactMap = [
            'Dump Truck' => 'Tinggi (Rugi Kapasitas Hauling)',
            'Excavator' => 'Sedang (Rugi Kapasitas Loading)',
            'Bulldozer' => 'Sedang (Rugi Kapasitas Stripping)',
            'Compactor' => 'Sedang (Rugi Kapasitas Compacting)',
            'Vibro Compactor' => 'Sedang (Rugi Kapasitas Compacting)',
            'Smooth Drum Compactor' => 'Sedang (Rugi Kapasitas Compacting)',
            'Trado' => 'Rendah (Peralatan Mobilisasi)',
            'Reclaimer Spreader' => 'Rendah (Peralatan Penunjang)',
        ];

        $categories = [];
        foreach ($catRows as $cr) {
            $c = (int) $cr['count'];
            $t = (string) $cr['type'];
            $impact = $impactMap[$t] ?? ($c > 5 ? 'Sedang (Idle Fleet)' : 'Rendah (Peralatan Penunjang)');
            $categories[] = [
                'type' => $t,
                'count' => $c,
                'ratio' => $totalUnits > 0 ? round(($c / $totalUnits) * 100.0, 1) : 0.0,
                'impact' => $impact,
            ];
        }

        return [
            'totalUnits' => $totalUnits,
            'locations' => $locations,
            'categories' => $categories,
        ];
    }
}
