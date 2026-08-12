<?php

use App\Enums\SantriGender;
use App\Enums\SantriStatus;
use App\Enums\TeamRole;
use App\Models\Santri;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function scopedAdmin(SantriGender $gender): User
{
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, [
        'role' => TeamRole::AdminLembaga->value,
        'santri_gender' => $gender,
    ]);
    $user->switchTeam($team);

    return $user;
}

function scopedTeam(User $user): Team
{
    return $user->teams()->where('is_personal', false)->firstOrFail();
}

test('a scoped admin only sees santri of their own gender', function () {
    $user = scopedAdmin(SantriGender::LakiLaki);
    $team = scopedTeam($user);

    $putra = Santri::factory()->create(['team_id' => $team->id, 'gender' => SantriGender::LakiLaki]);
    Santri::factory()->create(['team_id' => $team->id, 'gender' => SantriGender::Perempuan]);

    $this
        ->actingAs($user)
        ->get(route('admin.santri.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('santris.data', 1)
            ->where('santris.data.0.id', $putra->id)
            ->where('santriScope', SantriGender::LakiLaki->value));
});

test('a scoped admin sees all genders when not scoped', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::AdminLembaga->value]);
    $user->switchTeam($team);

    Santri::factory()->count(2)->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->get(route('admin.santri.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('santris.data', 2)
            ->where('santriScope', null));
});

test('a scoped admin is forced to their gender when creating a santri', function () {
    $user = scopedAdmin(SantriGender::LakiLaki);
    $team = scopedTeam($user);

    $this
        ->actingAs($user)
        ->post(route('admin.santri.store', $team), [
            'nis' => '2026001',
            'name' => 'Santri Putra',
            'gender' => SantriGender::Perempuan->value,
            'status' => SantriStatus::Aktif->value,
        ])
        ->assertRedirect();

    $santri = Santri::firstOrFail();

    expect($santri->gender)->toBe(SantriGender::LakiLaki);
});

test('the create form for a scoped admin only offers their gender', function () {
    $user = scopedAdmin(SantriGender::Perempuan);
    $team = scopedTeam($user);

    $this
        ->actingAs($user)
        ->get(route('admin.santri.create', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('santriScope', SantriGender::Perempuan->value)
            ->has('genders', 1)
            ->where('genders.0.value', SantriGender::Perempuan->value));
});

test('a scoped admin cannot edit a santri of another gender', function () {
    $user = scopedAdmin(SantriGender::LakiLaki);
    $team = scopedTeam($user);

    $santri = Santri::factory()->create(['team_id' => $team->id, 'gender' => SantriGender::Perempuan]);

    $this
        ->actingAs($user)
        ->get(route('admin.santri.edit', [$team, $santri]))
        ->assertNotFound();
});

test('a scoped admin cannot update a santri of another gender', function () {
    $user = scopedAdmin(SantriGender::LakiLaki);
    $team = scopedTeam($user);

    $santri = Santri::factory()->create(['team_id' => $team->id, 'gender' => SantriGender::Perempuan]);

    $this
        ->actingAs($user)
        ->put(route('admin.santri.update', [$team, $santri]), [
            'nis' => $santri->nis,
            'name' => 'Diubah',
            'gender' => SantriGender::Perempuan->value,
            'status' => $santri->status->value,
        ])
        ->assertNotFound();
});

test('a scoped admin cannot delete a santri of another gender', function () {
    $user = scopedAdmin(SantriGender::LakiLaki);
    $team = scopedTeam($user);

    $santri = Santri::factory()->create(['team_id' => $team->id, 'gender' => SantriGender::Perempuan]);

    $this
        ->actingAs($user)
        ->delete(route('admin.santri.destroy', [$team, $santri]))
        ->assertNotFound();

    $this->assertNotSoftDeleted('santris', ['id' => $santri->id]);
});

test('a scoped admin is forced to their gender when updating a santri', function () {
    $user = scopedAdmin(SantriGender::LakiLaki);
    $team = scopedTeam($user);

    $santri = Santri::factory()->create(['team_id' => $team->id, 'gender' => SantriGender::LakiLaki]);

    $this
        ->actingAs($user)
        ->put(route('admin.santri.update', [$team, $santri]), [
            'nis' => $santri->nis,
            'name' => 'Nama Baru',
            'gender' => SantriGender::Perempuan->value,
            'status' => $santri->status->value,
        ])
        ->assertRedirect();

    expect($santri->fresh()->gender)->toBe(SantriGender::LakiLaki);
});
