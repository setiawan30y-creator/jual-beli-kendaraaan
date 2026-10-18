<?php

namespace App\Support;

class Format
{
    public static function rupiah(float|int|string|null $value): string
    {
        return 'Rp'.number_format((float) $value, 0, ',', '.');
    }

    public static function km(?int $value): string
    {
        return $value === null ? '—' : number_format($value, 0, ',', '.').' km';
    }

    public static function maskPhone(?string $phone): string
    {
        if (! $phone) {
            return '—';
        }

        return substr($phone, 0, 4).str_repeat('•', max(strlen($phone) - 7, 0)).substr($phone, -3);
    }
}
