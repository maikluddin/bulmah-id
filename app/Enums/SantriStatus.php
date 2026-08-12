<?php

namespace App\Enums;

enum SantriStatus: string
{
    case Aktif = 'aktif';
    case Pendaftar = 'pendaftar';
    case Ditolak = 'ditolak';
    case Alumni = 'alumni';
    case Keluar = 'keluar';

    /**
     * Get the display label for the santri status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::Pendaftar => 'Pendaftar',
            self::Ditolak => 'Ditolak',
            self::Alumni => 'Alumni',
            self::Keluar => 'Keluar',
        };
    }
}
