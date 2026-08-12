<?php

namespace App\Enums;

enum HafalanType: string
{
    case HafalanBaru = 'hafalan_baru';
    case Murajaah = 'murajaah';

    /**
     * Get the display label for the hafalan type.
     */
    public function label(): string
    {
        return match ($this) {
            self::HafalanBaru => 'Hafalan Baru',
            self::Murajaah => 'Murajaah',
        };
    }
}
