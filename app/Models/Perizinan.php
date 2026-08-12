<?php

namespace App\Models;

use App\Enums\PerizinanJenis;
use App\Enums\PerizinanStatus;
use Database\Factories\PerizinanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int $santri_id
 * @property PerizinanJenis $jenis
 * @property string $alasan
 * @property Carbon $tanggal_mulai
 * @property Carbon|null $tanggal_selesai
 * @property PerizinanStatus $status
 * @property string|null $catatan
 * @property int|null $decided_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Santri $santri
 * @property-read User|null $decider
 */
#[Fillable(['team_id', 'santri_id', 'jenis', 'alasan', 'tanggal_mulai', 'tanggal_selesai', 'status', 'catatan', 'decided_by'])]
class Perizinan extends Model
{
    /** @use HasFactory<PerizinanFactory> */
    use HasFactory;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'jenis' => PerizinanJenis::IzinKeluar->value,
        'status' => PerizinanStatus::Pending->value,
    ];

    /**
     * Get the lembaga (team) the permit belongs to.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the santri the permit belongs to.
     *
     * @return BelongsTo<Santri, $this>
     */
    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    /**
     * Get the user who decided on the permit.
     *
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis' => PerizinanJenis::class,
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'status' => PerizinanStatus::class,
        ];
    }
}
