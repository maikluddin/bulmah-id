<?php

namespace App\Models;

use App\Enums\PerbaikanStatus;
use Database\Factories\PerbaikanSarprasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int|null $sarpras_item_id
 * @property int|null $pelapor_user_id
 * @property string $deskripsi
 * @property PerbaikanStatus $status
 * @property string|null $catatan
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read SarprasItem|null $item
 * @property-read User|null $pelapor
 */
#[Fillable(['team_id', 'sarpras_item_id', 'pelapor_user_id', 'deskripsi', 'status', 'catatan'])]
class PerbaikanSarpras extends Model
{
    /** @use HasFactory<PerbaikanSarprasFactory> */
    use HasFactory;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => PerbaikanStatus::Pending->value,
    ];

    /**
     * Get the lembaga (team) the request belongs to.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the sarpras item being repaired.
     *
     * @return BelongsTo<SarprasItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(SarprasItem::class, 'sarpras_item_id');
    }

    /**
     * Get the user who reported the request.
     *
     * @return BelongsTo<User, $this>
     */
    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pelapor_user_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PerbaikanStatus::class,
        ];
    }
}
