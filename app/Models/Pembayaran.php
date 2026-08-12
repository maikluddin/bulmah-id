<?php

namespace App\Models;

use App\Enums\PembayaranJenis;
use App\Enums\PembayaranMetode;
use App\Enums\PembayaranStatus;
use Database\Factories\PembayaranFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int $santri_id
 * @property PembayaranJenis $jenis
 * @property float $jumlah
 * @property Carbon $tanggal
 * @property PembayaranMetode $metode
 * @property PembayaranStatus $status
 * @property string|null $keterangan
 * @property int|null $recorded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Santri $santri
 * @property-read User|null $recorder
 */
#[Fillable(['team_id', 'santri_id', 'jenis', 'jumlah', 'tanggal', 'metode', 'status', 'keterangan', 'recorded_by'])]
class Pembayaran extends Model
{
    /** @use HasFactory<PembayaranFactory> */
    use HasFactory;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'jenis' => PembayaranJenis::Spp->value,
        'metode' => PembayaranMetode::Tunai->value,
        'status' => PembayaranStatus::Lunas->value,
    ];

    /**
     * Get the lembaga (team) the payment belongs to.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the santri the payment belongs to.
     *
     * @return BelongsTo<Santri, $this>
     */
    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    /**
     * Get the user who recorded the payment.
     *
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis' => PembayaranJenis::class,
            'jumlah' => 'decimal:2',
            'tanggal' => 'date',
            'metode' => PembayaranMetode::class,
            'status' => PembayaranStatus::class,
        ];
    }
}
