<?php

namespace App\Enums;

enum SarprasKategori: string
{
    case RuangKelas = 'ruang_kelas';
    case Asrama = 'asrama';
    case Masjid = 'masjid';
    case Perpustakaan = 'perpustakaan';
    case Laboratorium = 'laboratorium';
    case Olahraga = 'olahraga';
    case Kantin = 'kantin';
    case Lainnya = 'lainnya';

    /**
     * Get the display label for the kategori.
     */
    public function label(): string
    {
        return match ($this) {
            self::RuangKelas => 'Ruang Kelas',
            self::Asrama => 'Asrama',
            self::Masjid => 'Masjid',
            self::Perpustakaan => 'Perpustakaan',
            self::Laboratorium => 'Laboratorium',
            self::Olahraga => 'Olahraga',
            self::Kantin => 'Kantin',
            self::Lainnya => 'Lainnya',
        };
    }
}
