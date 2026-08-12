<?php

namespace App\Models;

use App\Concerns\GeneratesUniqueTeamSlugs;
use Database\Factories\YayasanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $logo_path
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $website
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Team> $lembaga
 */
#[Fillable(['name', 'slug', 'description', 'logo_path', 'address', 'phone', 'email', 'website'])]
class Yayasan extends Model
{
    /** @use HasFactory<YayasanFactory> */
    use GeneratesUniqueTeamSlugs, HasFactory, SoftDeletes;

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Yayasan $yayasan) {
            if (empty($yayasan->slug)) {
                $yayasan->slug = static::generateUniqueTeamSlug($yayasan->name);
            }
        });
    }

    /**
     * Get all lembaga (teams) under this yayasan.
     *
     * @return HasMany<Team, $this>
     */
    public function lembaga(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
