<?php

namespace App\Actions\Wali;

use App\Actions\Teams\CreateTeam;
use App\Enums\TeamRole;
use App\Models\Santri;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateWaliAccount
{
    public function __construct(private CreateTeam $createTeam)
    {
        //
    }

    /**
     * Create a wali (guardian) user account and link it to the given santri.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Team $team, array $data): User
    {
        return DB::transaction(function () use ($team, $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $this->createTeam->handle($user, "{$user->name}'s Team", isPersonal: true);

            $team->memberships()->create([
                'user_id' => $user->id,
                'role' => TeamRole::Member,
            ]);

            $this->linkSantri($team, $user, $data['santri_ids'] ?? [], $data['phone'] ?? null);

            return $user;
        });
    }

    /**
     * Link the account to the given santri and keep the denormalized wali
     * contact fields on each santri in sync.
     *
     * @param  array<int, int>  $santriIds
     */
    protected function linkSantri(Team $team, User $user, array $santriIds, ?string $phone): void
    {
        if ($santriIds === []) {
            return;
        }

        Santri::query()
            ->where('team_id', $team->id)
            ->whereIn('id', $santriIds)
            ->update([
                'wali_user_id' => $user->id,
                'wali_name' => $user->name,
                'wali_phone' => $phone,
            ]);
    }
}
