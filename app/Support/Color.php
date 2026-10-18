<?php

namespace App\Support;

/** Validasi kontras warna tema (WCAG). */
class Color
{
    public static function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        $rgb = array_map(fn ($c) => hexdec($c) / 255, str_split($hex, 2));
        $lin = array_map(fn ($c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4, $rgb);

        return 0.2126 * $lin[0] + 0.7152 * $lin[1] + 0.0722 * $lin[2];
    }

    public static function contrast(string $a, string $b): float
    {
        $l1 = self::luminance($a);
        $l2 = self::luminance($b);

        return round((max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05), 2);
    }

    /** Warna teks yang terbaca di atas latar tertentu. */
    public static function onColor(string $bg): string
    {
        return self::contrast($bg, '#ffffff') >= 4.5 ? '#ffffff' : '#111827';
    }
}
