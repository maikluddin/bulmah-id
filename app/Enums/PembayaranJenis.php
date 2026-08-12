<?php

namespace App\Enums;

enum PembayaranJenis: string
{
    case Spp = 'spp';
    case Makan = 'makan';
    case Kos = 'kos';
    case DaftarUlang = 'daftar_ulang';
    case Lainnya = 'lainnya';

    /**
     * Get the display label for the payment type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Spp => 'SPP',
            self::Makan => 'Uang Makan',
            self::Kos => 'Kos / Asrama',
            self::DaftarUlang => 'Daftar Ulang',
            self::Lainnya => 'Lainnya',
        };
    }
}
