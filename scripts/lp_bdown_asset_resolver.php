<?php
declare(strict_types=1);

/**
 * ServicePlan-BRA: LP Breakdown Asset Resolver Helper
 * Memetakan variasi string kode alat / nopol / serial number ke canonical asset_id SSOT.
 */

class LpAssetResolver
{
    private PDO $pdo;
    private array $assetById = [];
    private array $assetByPlate = [];
    private array $assetBySn = [];
    private array $manualAliases = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->loadIndex();
        $this->initAliases();
    }

    private function loadIndex(): void
    {
        $stmt = $this->pdo->query("SELECT asset_id, asset_code, serial_number, license_plate, make_model, category FROM assets");
        while ($a = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = strtoupper(trim($a['asset_id']));
            $this->assetById[$id] = $a;

            if (!empty($a['license_plate'])) {
                $cleanPlate = preg_replace('/[^A-Z0-9]/', '', strtoupper($a['license_plate']));
                if ($cleanPlate !== '') {
                    $this->assetByPlate[$cleanPlate] = $id;
                }
            }

            if (!empty($a['serial_number'])) {
                $cleanSn = strtoupper(trim($a['serial_number']));
                if ($cleanSn !== '') {
                    $this->assetBySn[$cleanSn] = $id;
                }
            }
        }
    }

    private function initAliases(): void
    {
        $this->manualAliases = [
            'DZR-0002' => 'DZ-00002',
            'DOZER SHANTUI SD16E / DZR-00006' => 'DZ-00006',
            'DOZER-BRA 05' => 'DZ-00005',
            'DOZER D6R (06) -- S/N S6X00348' => 'DZ-00006',
            'WTT-0001' => 'WT-00001',
            'BRA-57 WATERTANK FUSO -BK 8121 EO' => 'WT-00002',
            'WT-41002   - BK 8121 EO' => 'WT-00002',
            'WT-41001 -' => 'WT-00001',
            'CS-01 CEMENT SPREADER' => 'CS-41001',
            'CS-02 CEMENT SPREADER' => 'CS-41002',
            'FUSO TMC - XCMG B 9435 XFY' => 'TMC-00001',
            'TMC B 9430 XFY' => 'TMC-00005',
            'XCMG-B 9435 XFY' => 'TMC-00001',
            'BM 8441 NU ( XCMG Double Jack )' => 'TMC-31002',
            'BM 8452 NU' => 'TMC-31004',
            'FT-00001 - BA9016QU' => 'FTK-00001',
            'ML-41001 - BRA-03' => 'ML-00002',
            'BRA-03 - B 9096 ZYT' => 'DT-00003',
            'DT-BRA03' => 'DT-00003',
            'CC-00001 - ZCC600V' => 'CC-00002',
            'Padfoot XCMG XS205PD' => 'PF-00001',
            'Padfoot XS113E' => 'PF-00002',
            'SD-41003 - Padfoot XS113E' => 'PF-00002',
            'SD-41001 Compact CAT  CS 10' => 'PF-00010',
            'BRA-VIBRO 04 - CS10CC' => 'PF-00010',
            'VIBRO-04 - EKS PRABU T8F00361' => 'PF-00010',
            'Motor Grader' => 'MG-00001',
            'MG-00001 - 1372 - GR3' => 'MG-00001',
            'MG-41001' => 'MG-00001',
            'MG-410002' => 'MG-00002',
            'PM-41001 - BM8371CU' => 'PM-41002',
            'PM-41001 - BM8371CU - Lowboy' => 'PM-41002',
            'THRILLER - BM 8371 cu' => 'PM-41002',
            'FLAT BED B 9733 XPZ' => 'FBT-00001',
            'LB-00001 - PM-00001' => 'PM-00001',
            'PM-00001 - B 9012 ZEH - LB 00001' => 'PM-00001',
            'PM-00001 - B 9012 ZEH' => 'PM-00001',
            'PM-0001 - B 9012 ZEH' => 'PM-00001',
            'PM-00001' => 'PM-00001',
            'LB-00003 - MDN51552A' => 'PM-00003',
            'LB-00003' => 'PM-00003',
            'MB-00006 -- BM 7680 TU' => 'LV-00063',
            'MB-00007 - BM 7580 FU' => 'LV-00064',
            'HE-6.001 Exca mini' => 'EXC-00028',
            'HE-6.004 Exca mini' => 'EXC-00028',
            'HE.6 -0001 - WE217081' => 'EXC-00028',
            'BRA-02 EXCAV EKS PRABU' => 'EXC-00002',
            'Exc-BRA 02 -  C01530' => 'EXC-00002',
            'EXC-410002' => 'EXC-00002',
            'EXC-01009' => 'EXC-00009',
            'DT-01001' => 'DT-00001',
            'DT-01021' => 'DT-00021',
            'DT-01022' => 'DT-00022',
            'DT-01023' => 'DT-00023',
            'CAT RM-500B' => 'RM-00001',
            'FUSO FM 517 HS - BK 8121 EO' => 'WT-00002',
            'LV HILUX 9114 ZBA' => 'LV-00065',
            'LV BM 9834 DK' => 'LV-00045',
            'HILUX - B 9123 ZBA' => 'LV-00061',
            'LV HILUX B 9153 ZBA' => 'LV-00062',
            'LV HILUX B 9135 ZBA' => 'LV-00057',
            'ECXAV BRA-23 PC-200 SN: DBCH1801 EX-00012' => 'EXC-00012',
            'EXCA PC 200-10 MOCE (21) 745504 --SN C51503 EX-00003' => 'EXC-00003',
            'EXCA 320 GX (06) 745507- SN SYW01364 EX-00005' => 'EXC-00005',
            'DT-00002 - B9110ZYT' => 'DT-00002',
            'DT-00080 - B 9632 BIS' => 'DT-00080',
            'SD-00001 -  BW 211D - 40' => 'PF-00008',
            'SD-00002 - 961582391716' => 'PF-00009',
            'SD-00002' => 'PF-00009',
            'SD-00004 - 961884301945' => 'PF-00011',
            'SD-00005 - 961582391661' => 'PF-00012',
            'SD-00005' => 'PF-00012',
            'BOMAG DRUM (09) -- S/N 961582391661 SD-00005' => 'PF-00012',
            'SD-41001 -SD-00005' => 'PF-00012',
            'SD-41002' => 'PF-00013',
        ];
    }

    public function resolve(string $rawUnit): ?string
    {
        $raw = trim($rawUnit);
        if ($raw === '' || $raw === '`') {
            return null;
        }

        if (isset($this->manualAliases[$raw])) {
            return $this->manualAliases[$raw];
        }

        $cleanUpper = strtoupper($raw);
        if (isset($this->assetById[$cleanUpper])) {
            return $cleanUpper;
        }

        // Match License Plate (e.g. BM 9296 JO or B 9105 ZYT)
        if (preg_match('/\b([A-Z]{1,2})\s*([0-9]{1,4})\s*([A-Z]{1,3})\b/', $cleanUpper, $pltM)) {
            $p = $pltM[1] . $pltM[2] . $pltM[3];
            if (isset($this->assetByPlate[$p])) {
                return $this->assetByPlate[$p];
            }
        }

        // Match Serial Number if present
        if (preg_match('/\b([A-Z0-9]{8,15})\b/', $cleanUpper, $snM)) {
            if (isset($this->assetBySn[$snM[1]])) {
                return $this->assetBySn[$snM[1]];
            }
        }

        // Regex canonical pattern: e.g. DT-00049, EXC-00007, WT-00001
        if (preg_match('/\b([A-Z]{2,4})[-\s]*0*(\d+)\b/i', $cleanUpper, $m)) {
            $pfx = strtoupper($m[1]);
            $num = (int)$m[2];
            if ($pfx === 'EXCA' || $pfx === 'EX') $pfx = 'EXC';
            if ($pfx === 'DOZER' || $pfx === 'DZR') $pfx = 'DZ';
            if ($pfx === 'VIBRO' || $pfx === 'VB' || $pfx === 'SD') $pfx = 'PF';
            if ($pfx === 'FT') $pfx = 'FTK';

            $c5 = sprintf("%s-%05d", $pfx, $num);
            if (isset($this->assetById[$c5])) {
                return $c5;
            }

            $c4 = sprintf("%s-%04d", $pfx, $num);
            if (isset($this->assetById[$c4])) {
                return $c4;
            }
        }

        return null;
    }

    public function getAssetInfo(string $assetId): ?array
    {
        return $this->assetById[strtoupper($assetId)] ?? null;
    }
}
