<?php

namespace App\Models;

use App\Enums\HafalanKategoriJenis;
use Database\Factories\HafalanKategoriFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property HafalanKategoriJenis $jenis
 * @property string $nama
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Collection<int, Hafalan> $hafalans
 */
#[Fillable(['team_id', 'jenis', 'nama'])]
class HafalanKategori extends Model
{
    /** @use HasFactory<HafalanKategoriFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis' => HafalanKategoriJenis::class,
        ];
    }

    /**
     * Get the lembaga (team) the kategori belongs to.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the hafalan records using this kategori.
     *
     * @return HasMany<Hafalan, $this>
     */
    public function hafalans(): HasMany
    {
        return $this->hasMany(Hafalan::class, 'kategori_id');
    }
}
