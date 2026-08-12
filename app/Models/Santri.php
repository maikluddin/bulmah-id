<?php

namespace App\Models;

use App\Enums\SantriGender;
use App\Enums\SantriStatus;
use Database\Factories\SantriFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property string $nis
 * @property string|null $nisn
 * @property string $name
 * @property SantriGender $gender
 * @property string|null $birth_place
 * @property Carbon|null $birth_date
 * @property string|null $address
 * @property string|null $address_province
 * @property string|null $address_city
 * @property string|null $address_district
 * @property string|null $address_village
 * @property SantriStatus $status
 * @property int|null $kelas_id
 * @property int|null $wali_user_id
 * @property string|null $wali_name
 * @property string|null $wali_phone
 * @property Carbon|null $entry_date
 * @property Carbon|null $exit_date
 * @property string|null $photo_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Team $team
 * @property-read Kelas|null $kelas
 * @property-read User|null $wali
 * @property-read Collection<int, Hafalan> $hafalans
 * @property-read Collection<int, Absensi> $absensis
 * @property-read SantriWallet|null $wallet
 * @property-read Collection<int, WalletTransaction> $walletTransactions
 * @property-read Collection<int, Pembayaran> $pembayarans
 * @property-read Collection<int, Perizinan> $perizinans
 */
#[Fillable(['team_id', 'nis', 'nisn', 'name', 'gender', 'birth_place', 'birth_date', 'address', 'address_province', 'address_city', 'address_district', 'address_village', 'status', 'kelas_id', 'wali_user_id', 'wali_name', 'wali_phone', 'entry_date', 'exit_date', 'ditolak_alasan', 'photo_path'])]
class Santri extends Model
{
    /** @use HasFactory<SantriFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => SantriStatus::Aktif->value,
    ];

    /**
     * Get the lembaga (team) the santri belongs to.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the kelas the santri is assigned to.
     *
     * @return BelongsTo<Kelas, $this>
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * Get the wali (guardian) user account linked to the santri.
     *
     * @return BelongsTo<User, $this>
     */
    public function wali(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wali_user_id');
    }

    /**
     * Get the hafalan records of the santri.
     *
     * @return HasMany<Hafalan, $this>
     */
    public function hafalans(): HasMany
    {
        return $this->hasMany(Hafalan::class);
    }

    /**
     * Get the attendance records of the santri.
     *
     * @return HasMany<Absensi, $this>
     */
    public function absensis(): HasMany
    {
        return $this->hasMany(Absensi::class);
    }

    /**
     * Get the pocket money wallet of the santri.
     *
     * @return HasOne<SantriWallet, $this>
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(SantriWallet::class);
    }

    /**
     * Get the wallet transactions of the santri.
     *
     * @return HasMany<WalletTransaction, $this>
     */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Get the payments of the santri.
     *
     * @return HasMany<Pembayaran, $this>
     */
    public function pembayarans(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }

    /**
     * Generate the next auto NIS for a team based on the current year.
     */
    public static function nextNis(Team $team): string
    {
        $year = now()->format('Y');

        for ($attempt = 1; $attempt <= 1000; $attempt++) {
            $nis = $year.str_pad((string) $attempt, 4, '0', STR_PAD_LEFT);

            if (! static::query()->where('team_id', $team->id)->where('nis', $nis)->exists()) {
                return $nis;
            }
        }

        throw new \RuntimeException('Unable to generate a unique NIS.');
    }

    /**
     * Get the permits of the santri.
     *
     * @return HasMany<Perizinan, $this>
     */
    public function perizinans(): HasMany
    {
        return $this->hasMany(Perizinan::class);
    }

    /**
     * Scope a query to only include aktif santri.
     *
     * @param  Builder<Santri>  $query
     * @return Builder<Santri>
     */
    #[Scope]
    protected function aktif(Builder $query): Builder
    {
        return $query->where('status', SantriStatus::Aktif->value);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => SantriGender::class,
            'status' => SantriStatus::class,
            'birth_date' => 'date',
            'entry_date' => 'date',
            'exit_date' => 'date',
        ];
    }
}
