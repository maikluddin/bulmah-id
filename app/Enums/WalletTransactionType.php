<?php

namespace App\Enums;

enum WalletTransactionType: string
{
    case Setor = 'setor';
    case Penarikan = 'penarikan';

    /**
     * Get the display label for the wallet transaction type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Setor => 'Setor',
            self::Penarikan => 'Penarikan',
        };
    }
}
