<?php

namespace App\Concerns;

use App\Models\Santri;
use App\Models\Team;

trait ScopesSantri
{
    /**
     * Determine whether the santri is within the user's team and gender scope.
     */
    protected function santriInScope(Team $team, Santri $santri): bool
    {
        $genderScope = request()->user()->santriGenderFor($team);

        return $santri->team_id === $team->id
            && ($genderScope === null || $santri->gender === $genderScope);
    }

    /**
     * Abort the request when the santri is outside the user's scope.
     */
    protected function ensureSantriInScope(Team $team, Santri $santri): void
    {
        abort_unless($this->santriInScope($team, $santri), 404);
    }

    /**
     * Get the team data array passed to Inertia.
     *
     * @return array{id: int, name: string, slug: string}
     */
    protected function toTeamData(Team $team): array
    {
        return [
            'id' => $team->id,
            'name' => $team->name,
            'slug' => $team->slug,
        ];
    }

    /**
     * Get the santri data array passed to Inertia.
     *
     * @return array<string, mixed>
     */
    protected function toSantri(Santri $santri): array
    {
        return [
            'id' => $santri->id,
            'nis' => $santri->nis,
            'nisn' => $santri->nisn,
            'name' => $santri->name,
            'gender' => $santri->gender->value,
            'gender_label' => $santri->gender->label(),
            'status' => $santri->status->value,
            'status_label' => $santri->status->label(),
            'team' => $santri->team ? [
                'id' => $santri->team->id,
                'name' => $santri->team->name,
                'slug' => $santri->team->slug,
            ] : null,
        ];
    }

    /**
     * @param  array<int, \BackedEnum>  $cases
     * @return array<int, array{value: string, label: string}>
     */
    protected function toEnumOptions(array $cases): array
    {
        return array_map(
            fn ($case) => ['value' => $case->value, 'label' => $case->label()],
            $cases,
        );
    }
}
