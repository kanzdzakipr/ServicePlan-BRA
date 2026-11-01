<?php
declare(strict_types=1);

final class PartUnitConversion
{
    public static function normalizeAuthenticity(string $value): string
    {
        $normalized = strtoupper(trim($value));
        return match ($normalized) {
            'GENUINE', 'OEM' => 'GENUINE',
            'NON-GENUINE', 'NON GENUINE', 'AFTERMARKET' => 'NON_GENUINE',
            '', 'UNKNOWN', 'BELUM DIVERIFIKASI' => 'UNVERIFIED',
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
        if (!is_finite($quantity) || $quantity < 0) throw new InvalidArgumentException('Kuantitas tidak valid.');
        return round($quantity * self::validateFactor($factor), 6);
    }

    /** @return array{transaction_quantity:float,transaction_uom:string,conversion_factor:float,base_quantity:float,base_uom:string} */
    public static function snapshot(float $quantity, string $transactionUom, float $factor, string $baseUom): array
    {
        $transactionUom = trim($transactionUom);
        $baseUom = trim($baseUom);
        if ($transactionUom === '' || $baseUom === '') throw new InvalidArgumentException('Satuan transaksi dan satuan dasar wajib diisi.');
        return [
            'transaction_quantity' => round($quantity, 6),
            'transaction_uom' => $transactionUom,
            'conversion_factor' => self::validateFactor($factor),
            'base_quantity' => self::toBase($quantity, $factor),
            'base_uom' => $baseUom,
        ];
    }
}
