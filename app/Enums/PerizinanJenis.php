<?php

namespace App\Enums;

enum PerizinanJenis: string
{
    case IzinKeluar = 'izin_keluar';
    case IzinPulang = 'izin_pulang';
    case Lainnya = 'lainnya';

    /**
     * Get the display label for the permit type.
     */
    public function label(): string
    {
        return match ($this) {
            self::IzinKeluar => 'Izin Keluar',
            self::IzinPulang => 'Izin Pulang',
            self::Lainnya => 'Lainnya',
        };
    }
}
