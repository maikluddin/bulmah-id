<?php

namespace App\Enums;

enum PerbaikanStatus: string
{
    case Pending = 'pending';
    case Diproses = 'diproses';
    case Selesai = 'selesai';

    /**
     * Get the display label for the perbaikan status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Diproses => 'Diproses',
            self::Selesai => 'Selesai',
        };
    }
}
