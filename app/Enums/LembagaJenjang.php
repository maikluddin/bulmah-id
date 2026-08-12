<?php

namespace App\Enums;

enum LembagaJenjang: string
{
    case SMPI = 'smpi';
    case MAU = 'mau';
    case SMKI = 'smki';
    case Lainnya = 'lainnya';

    /**
     * Get the display label for the jenjang.
     */
    public function label(): string
    {
        return match ($this) {
            self::SMPI => 'SMP Islam',
            self::MAU => 'Madrasah Aliyah',
            self::SMKI => 'SMK Islam',
            self::Lainnya => 'Lainnya',
        };
    }

    /**
     * Get the default hafalan kategori template for this jenjang.
     *
     * @return array<int, array{jenis: string, nama: string}>
     */
    public function defaultHafalanKategoris(): array
    {
        return match ($this) {
            self::SMPI => [
                ['jenis' => HafalanKategoriJenis::Alquran->value, 'nama' => 'Juz Amma'],
                ['jenis' => HafalanKategoriJenis::Alquran->value, 'nama' => 'Jilid Yanbua'],
                ['jenis' => HafalanKategoriJenis::Kitab->value, 'nama' => 'Kitab Alfatih'],
            ],
            self::MAU => [
                ['jenis' => HafalanKategoriJenis::Kitab->value, 'nama' => 'Kitab Imrithi'],
                ['jenis' => HafalanKategoriJenis::Kitab->value, 'nama' => 'Kitab Alfiyah'],
                ['jenis' => HafalanKategoriJenis::Kitab->value, 'nama' => 'Vocabulary Inggris-Arab'],
            ],
            default => [],
        };
    }
}
