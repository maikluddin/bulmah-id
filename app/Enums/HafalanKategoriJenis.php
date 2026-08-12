<?php

namespace App\Enums;

enum HafalanKategoriJenis: string
{
    case Alquran = 'alquran';
    case Kitab = 'kitab';

    /**
     * Get the display label for the hafalan category type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Alquran => 'Al-Qur\'an',
            self::Kitab => 'Kitab',
        };
    }
}
