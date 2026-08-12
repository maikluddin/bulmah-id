<?php

namespace App\Enums;

enum PembayaranMetode: string
{
    case Tunai = 'tunai';
    case Transfer = 'transfer';

    /**
     * Get the display label for the payment method.
     */
    public function label(): string
    {
        return match ($this) {
            self::Tunai => 'Tunai',
            self::Transfer => 'Transfer',
        };
    }
}
