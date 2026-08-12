<?php

namespace App\Models;

use App\Concerns\GeneratesUniqueTeamSlugs;
use App\Enums\LembagaJenjang;
use App\Enums\LembagaType;
use App\Enums\TeamRole;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $yayasan_id
 * @property string $name
 * @property string $slug
 * @property bool $is_personal
 * @property string|null $type
 * @property string|null $type_label
 * @property string|null $jenjang
 * @property string|null $jenjang_label
 * @property string|null $description
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $photo_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Yayasan|null $yayasan
 * @property-read Collection<int, TeamInvitation> $invitations
 * @property-read Collection<int, Membership> $memberships
 * @property-read Collection<int, User> $members
 * @property-read Collection<int, Santri> $santris
 * @property-read Collection<int, Kelas> $kelas
 * @property-read Collection<int, HafalanKategori> $hafalanKategoris
 */
#[Fillable(['name', 'slug', 'is_personal', 'yayasan_id', 'type', 'jenjang', 'description', 'address', 'phone', 'email', 'photo_path'])]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use GeneratesUniqueTeamSlugs, HasFactory, SoftDeletes;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['type_label', 'jenjang_label'];

    /**
     * Get the yayasan the lembaga belongs to.
     *
     * @return BelongsTo<Yayasan, $this>
     */
    public function yayasan(): BelongsTo
    {
        return $this->belongsTo(Yayasan::class);
    }

    /**
     * Get all santri registered at this lembaga.
     *
     * @return HasMany<Santri, $this>
     */
    public function santris(): HasMany
    {
        return $this->hasMany(Santri::class);
    }

    /**
     * Get all kelas registered at this lembaga.
     *
     * @return HasMany<Kelas, $this>
     */
    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }

    /**
     * Get all hafalan categories registered at this lembaga.
     *
     * @return HasMany<HafalanKategori, $this>
     */
    public function hafalanKategoris(): HasMany
    {
        return $this->hasMany(HafalanKategori::class);
    }

    /**
     * Get all posts (berita) published by this lembaga.
     *
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get all post categories owned by this lembaga.
     *
     * @return HasMany<PostCategory, $this>
     */
    public function postCategories(): HasMany
    {
        return $this->hasMany(PostCategory::class);
    }

    /**
     * Get all sarpras items owned by this lembaga.
     *
     * @return HasMany<SarprasItem, $this>
     */
    public function sarprasItems(): HasMany
    {
        return $this->hasMany(SarprasItem::class);
    }

    /**
     * Get all perbaikan requests raised at this lembaga.
     *
     * @return HasMany<PerbaikanSarpras, $this>
     */
    public function perbaikanSarpras(): HasMany
    {
        return $this->hasMany(PerbaikanSarpras::class);
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Team $team) {
            if (empty($team->slug)) {
                $team->slug = static::generateUniqueTeamSlug($team->name);
            }
        });
    }

    /**
     * Get the team owner.
     */
    public function owner(): ?Model
    {
        return $this->members()
            ->wherePivot('role', TeamRole::Owner->value)
            ->first();
    }

    /**
     * Get all members of this team.
     *
     * @return BelongsToMany<User, $this, Membership, 'pivot'>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members', 'team_id', 'user_id')
            ->using(Membership::class)
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * Get all memberships for this team.
     *
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * Get all invitations for this team.
     *
     * @return HasMany<TeamInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_personal' => 'boolean',
        ];
    }

    /**
     * Get the display label for the lembaga type.
     */
    protected function getTypeLabelAttribute(): ?string
    {
        return $this->type !== null
            ? LembagaType::tryFrom($this->type)?->label()
            : null;
    }

    /**
     * Get the display label for the jenjang pendidikan.
     */
    protected function getJenjangLabelAttribute(): ?string
    {
        return $this->jenjang !== null
            ? LembagaJenjang::tryFrom($this->jenjang)?->label()
            : null;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
