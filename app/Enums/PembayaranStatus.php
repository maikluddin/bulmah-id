<?php

namespace App\Enums;

enum PembayaranStatus: string
{
    case Lunas = 'lunas';
    case Pending = 'pending';

    /**
     * Get the display label for the payment status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Lunas => 'Lunas',
            self::Pending => 'Pending',
        };
    }
}
