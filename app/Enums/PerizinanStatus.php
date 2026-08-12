<?php

namespace App\Enums;

enum PerizinanStatus: string
{
    case Pending = 'pending';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';

    /**
     * Get the display label for the permit status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Disetujui => 'Disetujui',
            self::Ditolak => 'Ditolak',
        };
    }
}
