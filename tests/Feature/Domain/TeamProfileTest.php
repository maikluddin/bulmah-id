<?php

use App\Enums\LembagaType;
use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

test('a lembaga profile can be stored', function () {
    $lembaga = Team::factory()->create([
        'type' => LembagaType::PondokPesantren->value,
        'description' => 'Pondok pesantren modern.',
        'address' => 'Jl. Pesantren No. 1',
        'phone' => '0263-123456',
        'email' => 'pondok@bulmah.id',
    ]);

    $this->assertDatabaseHas('teams', [
        'id' => $lembaga->id,
        'type' => LembagaType::PondokPesantren->value,
        'email' => 'pondok@bulmah.id',
    ]);

    expect($lembaga->type)->toBe(LembagaType::PondokPesantren->value);
});

test('the ustaz role is assignable to a lembaga member', function () {
    $team = Team::factory()->create();
    $ustaz = User::factory()->create();

    $team->members()->attach($ustaz, ['role' => TeamRole::Ustaz->value]);

    expect($ustaz->teamRole($team))->toBe(TeamRole::Ustaz);
    expect($ustaz->teams->contains($team))->toBeTrue();
});

test('ustaz role is a lower privilege than admin', function () {
    expect(TeamRole::Ustaz->isAtLeast(TeamRole::Admin))->toBeFalse();
    expect(TeamRole::Admin->isAtLeast(TeamRole::Ustaz))->toBeTrue();
});

test('the ustaz role is assignable when listing assignable roles', function () {
    expect(collect(TeamRole::assignable())->pluck('value'))
        ->toContain(TeamRole::Ustaz->value);
});

test('the admin lembaga role manages content but not team settings', function () {
    $permissions = TeamRole::AdminLembaga->permissions();

    expect($permissions)->toContain(TeamPermission::ManagePosts)
        ->toContain(TeamPermission::ManageCategories)
        ->toContain(TeamPermission::ManageSantri)
        ->toContain(TeamPermission::ManageLembaga)
        ->not->toContain(TeamPermission::UpdateTeam)
        ->not->toContain(TeamPermission::DeleteTeam)
        ->not->toContain(TeamPermission::AddMember)
        ->not->toContain(TeamPermission::UpdateMember)
        ->not->toContain(TeamPermission::RemoveMember)
        ->not->toContain(TeamPermission::CreateInvitation)
        ->not->toContain(TeamPermission::CancelInvitation);
});

test('the admin lembaga role is assignable to a lembaga member', function () {
    $team = Team::factory()->create();
    $adminLembaga = User::factory()->create();

    $team->members()->attach($adminLembaga, ['role' => TeamRole::AdminLembaga->value]);

    expect($adminLembaga->teamRole($team))->toBe(TeamRole::AdminLembaga);
    expect($adminLembaga->toTeamPermissions($team)->canManagePosts)->toBeTrue();
    expect($adminLembaga->toTeamPermissions($team)->canManageSantri)->toBeTrue();
    expect($adminLembaga->toTeamPermissions($team)->canUpdateTeam)->toBeFalse();
    expect($adminLembaga->toTeamPermissions($team)->canAddMember)->toBeFalse();
});

test('the admin lembaga role is assignable when listing assignable roles', function () {
    expect(collect(TeamRole::assignable())->pluck('value'))
        ->toContain(TeamRole::AdminLembaga->value);
});

test('admin lembaga role is lower privilege than admin but higher than ustaz', function () {
    expect(TeamRole::AdminLembaga->isAtLeast(TeamRole::Admin))->toBeFalse();
    expect(TeamRole::Admin->isAtLeast(TeamRole::AdminLembaga))->toBeTrue();
    expect(TeamRole::AdminLembaga->isAtLeast(TeamRole::Ustaz))->toBeTrue();
    expect(TeamRole::Ustaz->isAtLeast(TeamRole::AdminLembaga))->toBeFalse();
});

test('the admin pembayaran role manages keuangan only', function () {
    $permissions = TeamRole::AdminPembayaran->permissions();

    expect($permissions)->toContain(TeamPermission::ManagePembayaran)
        ->not->toContain(TeamPermission::UpdateTeam)
        ->not->toContain(TeamPermission::DeleteTeam)
        ->not->toContain(TeamPermission::ManagePosts)
        ->not->toContain(TeamPermission::ManageCategories)
        ->not->toContain(TeamPermission::ManageSantri)
        ->not->toContain(TeamPermission::ManageLembaga)
        ->not->toContain(TeamPermission::ManageMonitoring)
        ->not->toContain(TeamPermission::ManageSetoranHafalan)
        ->not->toContain(TeamPermission::AddMember)
        ->not->toContain(TeamPermission::UpdateMember)
        ->not->toContain(TeamPermission::RemoveMember)
        ->not->toContain(TeamPermission::CreateInvitation)
        ->not->toContain(TeamPermission::CancelInvitation);
});

test('the admin pembayaran role is assignable to a lembaga member', function () {
    $team = Team::factory()->create();
    $adminPembayaran = User::factory()->create();

    $team->members()->attach($adminPembayaran, ['role' => TeamRole::AdminPembayaran->value]);

    expect($adminPembayaran->teamRole($team))->toBe(TeamRole::AdminPembayaran);
    expect($adminPembayaran->toTeamPermissions($team)->canManagePembayaran)->toBeTrue();
    expect($adminPembayaran->toTeamPermissions($team)->canManageSantri)->toBeFalse();
    expect($adminPembayaran->toTeamPermissions($team)->canManageMonitoring)->toBeFalse();
});

test('the admin pembayaran role is assignable when listing assignable roles', function () {
    expect(collect(TeamRole::assignable())->pluck('value'))
        ->toContain(TeamRole::AdminPembayaran->value);
});

test('the admin lembaga role has the pembayaran permission', function () {
    expect(TeamRole::AdminLembaga->permissions())
        ->toContain(TeamPermission::ManagePembayaran);
});

test('the admin role has the pembayaran permission', function () {
    expect(TeamRole::Admin->permissions())
        ->toContain(TeamPermission::ManagePembayaran);
});

test('the ustaz role cannot manage pembayaran', function () {
    expect(TeamRole::Ustaz->permissions())
        ->not->toContain(TeamPermission::ManagePembayaran);
});
