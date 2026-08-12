<?php

namespace App\Models;

use App\Enums\SarprasKategori;
use App\Enums\SarprasKondisi;
use Database\Factories\SarprasItemFactory;
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
 * @property string $nama
 * @property SarprasKategori $kategori
 * @property string|null $lokasi
 * @property int $jumlah
 * @property SarprasKondisi $kondisi
 * @property string|null $keterangan
 * @property string|null $foto_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Collection<int, PerbaikanSarpras> $perbaikans
 */
#[Fillable(['team_id', 'nama', 'kategori', 'lokasi', 'jumlah', 'kondisi', 'keterangan', 'foto_path'])]
class SarprasItem extends Model
{
    /** @use HasFactory<SarprasItemFactory> */
    use HasFactory;

    /**
     * Get the lembaga (team) the item belongs to.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the perbaikan requests for this item.
     *
     * @return HasMany<PerbaikanSarpras, $this>
     */
    public function perbaikans(): HasMany
    {
        return $this->hasMany(PerbaikanSarpras::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kategori' => SarprasKategori::class,
            'kondisi' => SarprasKondisi::class,
            'jumlah' => 'integer',
        ];
    }
}
