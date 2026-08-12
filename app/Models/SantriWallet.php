<?php

namespace App\Models;

use Database\Factories\SantriWalletFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $santri_id
 * @property float $balance
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Santri $santri
 * @property-read Collection<int, WalletTransaction> $transactions
 */
#[Fillable(['santri_id', 'balance'])]
class SantriWallet extends Model
{
    /** @use HasFactory<SantriWalletFactory> */
    use HasFactory;

    /**
     * Get the santri who owns the wallet.
     *
     * @return BelongsTo<Santri, $this>
     */
    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    /**
     * Get the wallet transactions.
     *
     * @return HasMany<WalletTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'santri_id', 'santri_id');
    }

    /**
     * Get the balance formatted as a raw number for Inertia.
     */
    protected function balanceNumber(): Attribute
    {
        return Attribute::make(
            get: fn () => (float) $this->balance,
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
        ];
    }
}
