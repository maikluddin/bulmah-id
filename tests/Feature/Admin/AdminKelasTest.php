<?php

use App\Enums\TeamRole;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function kelasAdmin(): User
{
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($team);

    return $user;
}

function kelasTeam(User $user): Team
{
    return $user->teams()->where('is_personal', false)->firstOrFail();
}

test('a member cannot access the kelas page', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $this
        ->actingAs($user)
        ->get(route('admin.kelas.index', $team))
        ->assertForbidden();
});

test('an admin can create a kelas with an assigned ustad', function () {
    $user = kelasAdmin();
    $team = kelasTeam($user);
    $ustad = User::factory()->create();
    $team->members()->attach($ustad, ['role' => TeamRole::Ustaz->value]);

    $this
        ->actingAs($user)
        ->from(route('admin.kelas.index', $team))
        ->post(route('admin.kelas.store', $team), [
            'nama' => 'Kelas Tahfidz A',
            'ustad_user_id' => $ustad->id,
        ])
        ->assertRedirect(route('admin.kelas.index', $team));

    $kelas = Kelas::firstOrFail();

    expect($kelas->nama)->toBe('Kelas Tahfidz A')
        ->and($kelas->team_id)->toBe($team->id)
        ->and($kelas->ustad_user_id)->toBe($ustad->id);
});

test('a kelas cannot be assigned to a user outside the team', function () {
    $user = kelasAdmin();
    $team = kelasTeam($user);
    $outsider = User::factory()->create();

    $this
        ->actingAs($user)
        ->from(route('admin.kelas.index', $team))
        ->post(route('admin.kelas.store', $team), [
            'nama' => 'Kelas A',
            'ustad_user_id' => $outsider->id,
        ])
        ->assertSessionHasErrors('ustad_user_id');

    expect(Kelas::count())->toBe(0);
});

test('an admin can update the ustad assigned to a kelas', function () {
    $user = kelasAdmin();
    $team = kelasTeam($user);
    $ustad = User::factory()->create();
    $team->members()->attach($ustad, ['role' => TeamRole::Ustaz->value]);
    $kelas = Kelas::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->from(route('admin.kelas.index', $team))
        ->put(route('admin.kelas.update', [$team, $kelas]), [
            'nama' => $kelas->nama,
            'ustad_user_id' => $ustad->id,
        ])
        ->assertRedirect(route('admin.kelas.index', $team));

    expect($kelas->fresh()->ustad_user_id)->toBe($ustad->id);
});

test('an admin can remove the ustad assignment from a kelas', function () {
    $user = kelasAdmin();
    $team = kelasTeam($user);
    $ustad = User::factory()->create();
    $team->members()->attach($ustad, ['role' => TeamRole::Ustaz->value]);
    $kelas = Kelas::factory()->create([
        'team_id' => $team->id,
        'ustad_user_id' => $ustad->id,
    ]);

    $this
        ->actingAs($user)
        ->from(route('admin.kelas.index', $team))
        ->put(route('admin.kelas.update', [$team, $kelas]), [
            'nama' => $kelas->nama,
            'ustad_user_id' => '',
        ])
        ->assertRedirect(route('admin.kelas.index', $team));

    expect($kelas->fresh()->ustad_user_id)->toBeNull();
});

test('the kelas page shows the assigned ustad', function () {
    $user = kelasAdmin();
    $team = kelasTeam($user);
    $ustad = User::factory()->create();
    $team->members()->attach($ustad, ['role' => TeamRole::Ustaz->value]);
    $kelas = Kelas::factory()->create([
        'team_id' => $team->id,
        'ustad_user_id' => $ustad->id,
    ]);

    $this
        ->actingAs($user)
        ->get(route('admin.kelas.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/kelas/index')
            ->has('kelas', 1)
            ->where('kelas.0.id', $kelas->id)
            ->where('kelas.0.ustad.id', $ustad->id)
            ->where('kelas.0.ustad.name', $ustad->name)
            ->has('ustads', 2));
});

test('a kelas with santri cannot be deleted', function () {
    $user = kelasAdmin();
    $team = kelasTeam($user);
    $kelas = Kelas::factory()->create(['team_id' => $team->id]);
    Santri::factory()->create(['team_id' => $team->id, 'kelas_id' => $kelas->id]);

    $this
        ->actingAs($user)
        ->from(route('admin.kelas.index', $team))
        ->delete(route('admin.kelas.destroy', [$team, $kelas]))
        ->assertSessionHasErrors('kelas');

    expect(Kelas::count())->toBe(1);
});
