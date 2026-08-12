<?php

namespace App\Models;

use App\Enums\HafalanType;
use Database\Factories\HafalanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int $santri_id
 * @property int|null $kategori_id
 * @property int|null $ustad_user_id
 * @property HafalanType $type
 * @property int|null $juz
 * @property string $surat
 * @property int|null $ayat_from
 * @property int|null $ayat_to
 * @property Carbon $tanggal
 * @property string|null $keterangan
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Santri $santri
 * @property-read HafalanKategori|null $kategori
 * @property-read User|null $ustad
 */
#[Fillable(['team_id', 'santri_id', 'kategori_id', 'ustad_user_id', 'type', 'juz', 'surat', 'ayat_from', 'ayat_to', 'tanggal', 'keterangan'])]
class Hafalan extends Model
{
    /** @use HasFactory<HafalanFactory> */
    use HasFactory;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => HafalanType::HafalanBaru->value,
    ];

    /**
     * Get the lembaga (team) the hafalan record belongs to.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the santri who recorded the setoran.
     *
     * @return BelongsTo<Santri, $this>
     */
    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    /**
     * Get the ustad who received the setoran.
     *
     * @return BelongsTo<User, $this>
     */
    public function ustad(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ustad_user_id');
    }

    /**
     * Get the kategori the hafalan record belongs to.
     *
     * @return BelongsTo<HafalanKategori, $this>
     */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(HafalanKategori::class, 'kategori_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => HafalanType::class,
            'juz' => 'integer',
            'ayat_from' => 'integer',
            'ayat_to' => 'integer',
            'tanggal' => 'date',
        ];
    }
}
