<?php

namespace App\Policies;

use App\Enums\TeamPermission;
use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::UpdateTeam);
    }

    /**
     * Determine whether the user can leave the team.
     */
    public function leave(User $user, Team $team): bool
    {
        return ! $team->is_personal
            && $user->belongsToTeam($team)
            && ! $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can add a member to the team.
     */
    public function addMember(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::AddMember);
    }

    /**
     * Determine whether the user can update a member's role in the team.
     */
    public function updateMember(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::UpdateMember);
    }

    /**
     * Determine whether the user can remove a member from the team.
     */
    public function removeMember(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::RemoveMember);
    }

    /**
     * Determine whether the user can invite members to the team.
     */
    public function inviteMember(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::CreateInvitation);
    }

    /**
     * Determine whether the user can cancel invitations.
     */
    public function cancelInvitation(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::CancelInvitation);
    }

    /**
     * Determine whether the user can manage the team's posts (berita).
     */
    public function managePosts(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::ManagePosts);
    }

    /**
     * Determine whether the user can manage the team's post categories.
     */
    public function manageCategories(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::ManageCategories);
    }

    /**
     * Determine whether the user can manage the team's santri.
     */
    public function manageSantri(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::ManageSantri);
    }

    /**
     * Determine whether the user can manage the lembaga (team) public profile.
     */
    public function manageLembaga(User $user, Team $team): bool
    {
        return ! $team->is_personal && $user->hasTeamPermission($team, TeamPermission::ManageLembaga);
    }

    /**
     * Determine whether the user can manage the santri monitoring modules.
     */
    public function manageMonitoring(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::ManageMonitoring);
    }

    /**
     * Determine whether the user can record hafalan setoran (ustad view).
     */
    public function manageSetoranHafalan(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::ManageSetoranHafalan);
    }

    /**
     * Determine whether the user can manage the lembaga's keuangan and pembayaran.
     */
    public function managePembayaran(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::ManagePembayaran);
    }

    /**
     * Determine whether the user can manage the lembaga's sarana & prasarana.
     */
    public function manageSarpras(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::ManageSarpras);
    }

    /**
     * Determine whether the user can access the wali (guardian) portal
     * for the given team, i.e. they supervise at least one santri.
     */
    public function accessWaliPortal(User $user, Team $team): bool
    {
        return $user->santris()->where('team_id', $team->id)->exists();
    }

    /**
     * Determine whether the user can manage the team's wali (guardian) accounts.
     */
    public function manageWali(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::ManageWali);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Team $team): bool
    {
        return ! $team->is_personal && $user->hasTeamPermission($team, TeamPermission::DeleteTeam);
    }
}
