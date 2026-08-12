<?php

namespace App\Enums;

enum LembagaType: string
{
    case PondokPesantren = 'pondok_pesantren';
    case Madrasah = 'madrasah';
    case Sekolah = 'sekolah';
    case Tahfidz = 'tahfidz';
    case Tpq = 'tpq';
    case Lainnya = 'lainnya';

    /**
     * Get the display label for the lembaga type.
     */
    public function label(): string
    {
        return match ($this) {
            self::PondokPesantren => 'Pondok Pesantren',
            self::Madrasah => 'Madrasah',
            self::Sekolah => 'Sekolah',
            self::Tahfidz => 'Tahfidz',
            self::Tpq => 'TPQ',
            self::Lainnya => 'Lainnya',
        };
    }
}
