<?php
declare(strict_types=1);

final class ReportVoidPolicy
{
    public static function reason(mixed $value): string
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('Alasan pembatalan laporan wajib diisi.');
        }
        $reason = trim($value);
        if ($reason === '') {
            throw new InvalidArgumentException('Alasan pembatalan laporan wajib diisi.');
        }
        if (mb_strlen($reason, 'UTF-8') > 500) {
            throw new InvalidArgumentException('Alasan pembatalan laporan maksimal 500 karakter.');
        }
        return $reason;
    }
}
