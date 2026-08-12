<?php

namespace App\Enums;

enum SantriGender: string
{
    case LakiLaki = 'laki-laki';
    case Perempuan = 'perempuan';

    /**
     * Get the display label for the santri gender.
     */
    public function label(): string
    {
        return match ($this) {
            self::LakiLaki => 'Laki-laki',
            self::Perempuan => 'Perempuan',
        };
    }
}
