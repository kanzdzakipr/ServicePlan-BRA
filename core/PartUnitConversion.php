<?php
declare(strict_types=1);

final class PartUnitConversion
{
    public const PART_GROUPS = [
        'Filter Udara',
        'Filter Oli',
        'Filter Solar',
        'Filter Hidrolik',
        'Pelumas',
        'Ban & Velg',
        'Aki / Baterai',
        'Cutting Bit & Blade',
        'Selang & Fitting',
        'Engine & Drivetrain',
        'Brake & Chassis',
        'Fast Moving & Hardware',
        'Consumable & Workshop',
        'Lainnya',
    ];

    public static function normalizeAuthenticity(string $value): string
    {
        $normalized = strtoupper(trim($value));
        return match ($normalized) {
            'GENUINE', 'OEM', 'ASLI' => 'GENUINE',
            'NON-GENUINE', 'NON GENUINE', 'NON_GENUINE', 'AFTERMARKET', 'OEM_AFTERMARKET' => 'NON_GENUINE',
            '', 'UNKNOWN', 'UNVERIFIED', 'BELUM DIVERIFIKASI' => 'UNVERIFIED',
            default => throw new InvalidArgumentException('Authenticity harus Genuine, Non-Genuine, atau Unverified.'),
        };
    }

    public static function validateFactor(float $factor): float
    {
        if (!is_finite($factor) || $factor <= 0 || $factor > 1000000) {
            throw new InvalidArgumentException('Faktor konversi harus lebih besar dari nol dan masuk akal.');
        }
        return round($factor, 6);
    }

    public static function toBase(float $quantity, float $factor): float
    {
        if (!is_finite($quantity) || $quantity < 0) {
            throw new InvalidArgumentException('Kuantitas tidak valid.');
        }
        return round($quantity * self::validateFactor($factor), 6);
    }

    public static function toTransaction(float $baseQuantity, float $factor): float
    {
        $factor = self::validateFactor($factor);
        if (!is_finite($baseQuantity) || $baseQuantity < 0) {
            throw new InvalidArgumentException('Kuantitas dasar tidak valid.');
        }
        return round($baseQuantity / $factor, 6);
    }

    /** @return array{transaction_quantity:float,transaction_uom:string,conversion_factor:float,base_quantity:float,base_uom:string} */
    public static function snapshot(float $quantity, string $transactionUom, float $factor, string $baseUom): array
    {
        $transactionUom = trim($transactionUom);
        $baseUom = trim($baseUom);
        if ($transactionUom === '' || $baseUom === '') {
            throw new InvalidArgumentException('Satuan transaksi dan satuan dasar wajib diisi.');
        }
        return [
            'transaction_quantity' => round($quantity, 6),
            'transaction_uom' => $transactionUom,
            'conversion_factor' => self::validateFactor($factor),
            'base_quantity' => self::toBase($quantity, $factor),
            'base_uom' => $baseUom,
        ];
    }

    public static function inferPartGroup(string $name, string $category = ''): string
    {
        $n = strtolower($name);
        $c = strtolower($category);

        // Filters
        if (str_contains($n, 'air filter') || str_contains($n, 'air cleaner') || str_contains($n, 'filter udara') || str_contains($n, 'breather air')) {
            return 'Filter Udara';
        }
        if (str_contains($n, 'oil filter') || str_contains($n, 'filter oli') || str_contains($n, 'engine oil filter') || str_contains($n, 'filter as engine oil')) {
            return 'Filter Oli';
        }
        if (str_contains($n, 'fuel filter') || str_contains($n, 'water separator') || str_contains($n, 'filter solar') || str_contains($n, 'fuel 2mic') || str_contains($n, 'filter as-water sep')) {
            return 'Filter Solar';
        }
        if (str_contains($n, 'hydraulic filter') || str_contains($n, 'filter hidrolik') || str_contains($n, 'transmission filter') || str_contains($n, 'element hydraulic')) {
            return 'Filter Hidrolik';
        }
        if ($c === 'filter' || str_contains($n, 'filter') || str_contains($n, 'element') || str_contains($n, 'cartridge')) {
            return 'Filter Udara';
        }

        // Oils, lubricants & chemicals
        if (
            str_contains($n, 'oli ') || str_contains($n, 'oil') || str_contains($n, 'lubricant')
            || str_contains($n, '15w-40') || str_contains($n, '15w40') || str_contains($n, 'deo')
            || str_contains($n, 'tdto') || str_contains($n, 'hydo') || str_contains($n, 'tellus')
            || str_contains($n, 'grease') || str_contains($n, 'gemuk') || str_contains($n, 'ad blue')
            || str_contains($n, 'coolant') || str_contains($n, 'minyak rem') || str_contains($n, 'brake fluid')
            || str_contains($n, 'gardan') || str_contains($n, 'transmisi')
        ) {
            return 'Pelumas';
        }

        // Tires & Wheels
        if (str_contains($n, 'ban ') || str_contains($n, 'tire') || str_contains($n, 'tyre') || str_contains($n, 'velg') || str_contains($n, 'pelek') || str_contains($n, 'flap') || str_contains($n, 'selendang')) {
            return 'Ban & Velg';
        }

        // Batteries
        if (str_contains($n, 'aki') || str_contains($n, 'accu') || str_contains($n, 'battery') || str_contains($n, 'baterai') || str_contains($n, 'air aki')) {
            return 'Aki / Baterai';
        }

        // Cutting Bits & Blades
        if (str_contains($n, 'cutting bit') || str_contains($n, 'tooth') || str_contains($n, 'toot') || str_contains($n, 'adapter toot') || str_contains($n, 'blade') || str_contains($n, 'end bit') || str_contains($n, 'kuku')) {
            return 'Cutting Bit & Blade';
        }

        // Hoses & Fittings
        if (str_contains($n, 'hose') || str_contains($n, 'selang') || str_contains($n, 'nepel') || str_contains($n, 'fitting') || str_contains($n, 'nipple') || str_contains($n, 'quick coupler')) {
            return 'Selang & Fitting';
        }

        // Engine & Drivetrain
        if (str_contains($n, 'piston') || str_contains($n, 'liner') || str_contains($n, 'ring piston') || str_contains($n, 'gasket') || str_contains($n, 'packing') || str_contains($n, 'seal') || str_contains($n, 'bearing') || str_contains($n, 'v-belt') || str_contains($n, 'fan belt') || str_contains($n, 'alternator') || str_contains($n, 'starter') || str_contains($n, 'water pump') || str_contains($n, 'turbo') || str_contains($n, 'radiator')) {
            return 'Engine & Drivetrain';
        }

        // Brake & Suspension / Chassis
        if (str_contains($n, 'rem') || str_contains($n, 'brake') || str_contains($n, 'tromol') || str_contains($n, 'kampas') || str_contains($n, 'chamber') || str_contains($n, 'spring') || str_contains($n, 'per ') || str_contains($n, 'u-bolt') || str_contains($n, 'tie rod')) {
            return 'Brake & Chassis';
        }

        // Hardware & Fasteners
        if (str_contains($n, 'baut') || str_contains($n, 'mur ') || str_contains($n, 'bolt') || str_contains($n, 'nut') || str_contains($n, 'ring ') || str_contains($n, 'washer') || str_contains($n, 'klem') || str_contains($n, 'clamp') || str_contains($n, 'lampu') || str_contains($n, 'lamp') || str_contains($n, 'fuse') || str_contains($n, 'sekring') || str_contains($n, 'relay') || str_contains($n, 'switch')) {
            return 'Fast Moving & Hardware';
        }

        // Workshop consumables
        if (str_contains($n, 'gerinda') || str_contains($n, 'las ') || str_contains($n, 'elektroda') || str_contains($n, 'majun') || str_contains($n, 'wd-40') || str_contains($n, 'lem ') || str_contains($n, 'cat ') || str_contains($n, 'dextone') || str_contains($n, 'thinner') || str_contains($n, 'amplas') || str_contains($n, 'sarung tangan')) {
            return 'Consumable & Workshop';
        }

        return match ($c) {
            'filter' => 'Filter Udara',
            'hose' => 'Selang & Fitting',
            'cutting bit' => 'Cutting Bit & Blade',
            'tire' => 'Ban & Velg',
            'battery' => 'Aki / Baterai',
            'engine part' => 'Engine & Drivetrain',
            'hydraulic' => 'Selang & Fitting',
            'fast moving' => 'Fast Moving & Hardware',
            default => 'Lainnya',
        };
    }

    public static function inferAuthenticity(string $name, string $partNumber = '', string $brandHint = ''): string
    {
        $text = strtoupper($name . ' ' . $partNumber . ' ' . $brandHint);
        if (str_contains($text, 'GENUINE') || str_contains($text, 'OEM') || str_contains($text, 'ORIGINAL') || str_contains($text, 'CATERPILLAR') || str_contains($text, 'KOMATSU') || str_contains($text, 'HINO') || str_contains($text, 'BOMAG')) {
            return 'GENUINE';
        }
        if (str_contains($text, 'NON-GENUINE') || str_contains($text, 'NON GENUINE') || str_contains($text, 'DONALDSON') || str_contains($text, 'DONALSON') || str_contains($text, 'SAKURA') || str_contains($text, 'BALDWIN') || str_contains($text, 'SURE') || str_contains($text, 'JIMCO') || str_contains($text, 'ASPIRA') || str_contains($text, 'AFTERMARKET') || str_contains($text, 'LOKAL')) {
            return 'NON_GENUINE';
        }
        // Known Caterpillar OEM part number patterns (e.g. 1R0751, 1R0739, 4385386, 3E9848)
        $cleanPn = strtoupper(trim($partNumber));
        if (preg_match('/^(1R|3E|5I|6Y|7X|8F|5F|438|093|293|179|131|177)[0-9A-Z]/', $cleanPn)) {
            return 'GENUINE';
        }
        return 'UNVERIFIED';
    }

    /** @return array{base_uom:string, transaction_uom:string, conversion_factor:float} */
    public static function inferUom(string $name, string $rawUom = 'Pcs'): array
    {
        $n = strtolower($name);
        $u = strtolower(trim($rawUom));

        // Liquid in drums / pails
        if (str_contains($n, 'drum 209') || str_contains($n, '209l') || str_contains($n, '209 liter')) {
            return ['base_uom' => 'Liter', 'transaction_uom' => 'Drum', 'conversion_factor' => 209.0];
        }
        if (str_contains($n, 'drum') || (str_contains($n, 'oli') && $u === 'drum') || (str_contains($n, 'oil') && $u === 'drum') || (str_contains($n, 'pelumas') && $u === 'drum')) {
            return ['base_uom' => 'Liter', 'transaction_uom' => 'Drum', 'conversion_factor' => 200.0];
        }
        if (str_contains($n, 'pail 20') || str_contains($n, 'pail') || (str_contains($n, 'oli') && $u === 'pail')) {
            return ['base_uom' => 'Liter', 'transaction_uom' => 'Pail', 'conversion_factor' => 20.0];
        }
        if (str_contains($n, 'ad blue 10') || str_contains($n, '10 liter') || str_contains($n, '10l')) {
            return ['base_uom' => 'Liter', 'transaction_uom' => 'Can', 'conversion_factor' => 10.0];
        }
        if (str_contains($n, 'air aki') && (str_contains($n, 'derigen') || str_contains($n, 'drigen') || $u === 'derigen' || $u === 'drigen')) {
            return ['base_uom' => 'Liter', 'transaction_uom' => 'Derigen', 'conversion_factor' => 5.0];
        }
        if (str_contains($n, 'air aki') && (str_contains($n, 'btl') || $u === 'btl')) {
            return ['base_uom' => 'Liter', 'transaction_uom' => 'Btl', 'conversion_factor' => 1.0];
        }
        if (str_contains($n, 'grease') || str_contains($n, 'gemuk')) {
            if (str_contains($n, 'drum') || $u === 'drum') {
                return ['base_uom' => 'Kg', 'transaction_uom' => 'Drum', 'conversion_factor' => 180.0];
            }
            if (str_contains($n, 'pail') || $u === 'pail') {
                return ['base_uom' => 'Kg', 'transaction_uom' => 'Pail', 'conversion_factor' => 15.0];
            }
        }
        if ($u === 'ltr' || $u === 'liter') {
            return ['base_uom' => 'Liter', 'transaction_uom' => 'Liter', 'conversion_factor' => 1.0];
        }
        if ($u === 'meter' || $u === 'mtr' || str_contains($n, 'selang roll')) {
            return ['base_uom' => 'Meter', 'transaction_uom' => $u === 'roll' ? 'Roll' : 'Meter', 'conversion_factor' => $u === 'roll' ? 50.0 : 1.0];
        }
        if ($u === 'unit' || str_contains($n, 'angkong')) {
            return ['base_uom' => 'Unit', 'transaction_uom' => 'Unit', 'conversion_factor' => 1.0];
        }
        if ($u === 'set' || $u === 'pasang') {
            return ['base_uom' => 'Set', 'transaction_uom' => 'Set', 'conversion_factor' => 1.0];
        }

        return ['base_uom' => 'Pcs', 'transaction_uom' => 'Pcs', 'conversion_factor' => 1.0];
    }
}
