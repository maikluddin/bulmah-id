<?php

use App\Enums\SantriGender;
use App\Enums\SantriStatus;
use App\Enums\TeamRole;
use App\Models\Santri;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('unauthenticated users are redirected to login', function () {
    $team = Team::factory()->create();

    $this->get(route('admin.pendaftaran.index', $team))->assertRedirectToRoute('login');
});

test('a team owner can view the pendaftaran index', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $pendaftar = Santri::factory()->create([
        'team_id' => $team->id,
        'nis' => null,
        'status' => SantriStatus::Pendaftar->value,
    ]);
    Santri::factory()->create([
        'team_id' => $team->id,
        'status' => SantriStatus::Aktif->value,
    ]);

    $this
        ->actingAs($user)
        ->get(route('admin.pendaftaran.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/pendaftaran/index')
            ->has('pendaftarans.data', 1)
            ->where('pendaftarans.data.0.id', $pendaftar->id)
            ->where('pendingCount', 1));
});

test('a non-member cannot view the pendaftaran index', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    $this
        ->actingAs($user)
        ->get(route('admin.pendaftaran.index', $team))
        ->assertForbidden();
});

test('the pendaftaran index filters by status', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Santri::factory()->create([
        'team_id' => $team->id,
        'nis' => null,
        'status' => SantriStatus::Pendaftar->value,
    ]);
    $ditolak = Santri::factory()->create([
        'team_id' => $team->id,
        'nis' => null,
        'status' => SantriStatus::Ditolak->value,
        'ditolak_alasan' => 'Kuota penuh',
    ]);

    $this
        ->actingAs($user)
        ->get(route('admin.pendaftaran.index', [$team, 'status' => SantriStatus::Ditolak->value]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('pendaftarans.data', 1)
            ->where('pendaftarans.data.0.id', $ditolak->id)
            ->where('pendaftarans.data.0.ditolak_alasan', 'Kuota penuh'));
});

test('a team owner can accept a pendaftar', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $pendaftar = Santri::factory()->create([
        'team_id' => $team->id,
        'nis' => null,
        'status' => SantriStatus::Pendaftar->value,
    ]);

    $this
        ->actingAs($user)
        ->post(route('admin.pendaftaran.accept', [$team, $pendaftar]))
        ->assertRedirect();

    $pendaftar->refresh();

    expect($pendaftar->status)->toBe(SantriStatus::Aktif)
        ->and($pendaftar->nis)->not->toBeNull()
        ->and($pendaftar->entry_date)->not->toBeNull()
        ->and($pendaftar->wallet)->not->toBeNull();
});

test('accepting an already processed pendaftar does nothing', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $santri = Santri::factory()->create([
        'team_id' => $team->id,
        'nis' => '2026001',
        'status' => SantriStatus::Aktif->value,
    ]);

    $this
        ->actingAs($user)
        ->post(route('admin.pendaftaran.accept', [$team, $santri]))
        ->assertRedirect();

    expect($santri->fresh()->nis)->toBe('2026001');
});

test('a team owner can reject a pendaftar with a reason', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $pendaftar = Santri::factory()->create([
        'team_id' => $team->id,
        'nis' => null,
        'status' => SantriStatus::Pendaftar->value,
    ]);

    $this
        ->actingAs($user)
        ->post(route('admin.pendaftaran.reject', [$team, $pendaftar]), [
            'alasan' => 'Kuota kelas penuh',
        ])
        ->assertRedirect();

    $pendaftar->refresh();

    expect($pendaftar->status)->toBe(SantriStatus::Ditolak)
        ->and($pendaftar->ditolak_alasan)->toBe('Kuota kelas penuh');
});

test('rejecting a pendaftar requires a reason', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $pendaftar = Santri::factory()->create([
        'team_id' => $team->id,
        'nis' => null,
        'status' => SantriStatus::Pendaftar->value,
    ]);

    $this
        ->actingAs($user)
        ->post(route('admin.pendaftaran.reject', [$team, $pendaftar]), [])
        ->assertSessionHasErrors('alasan');

    expect($pendaftar->fresh()->status)->toBe(SantriStatus::Pendaftar);
});

test('a pendaftar from another team cannot be accepted', function () {
    $user = User::factory()->create();
    $teamA = Team::factory()->create();
    $teamB = Team::factory()->create();
    $teamA->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $pendaftar = Santri::factory()->create([
        'team_id' => $teamB->id,
        'nis' => null,
        'status' => SantriStatus::Pendaftar->value,
    ]);

    $this
        ->actingAs($user)
        ->post(route('admin.pendaftaran.accept', [$teamA, $pendaftar]))
        ->assertNotFound();

    expect($pendaftar->fresh()->status)->toBe(SantriStatus::Pendaftar);
});

test('a gender-scoped user only sees pendaftar of their gender', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, [
        'role' => TeamRole::AdminLembaga->value,
        'santri_gender' => SantriGender::LakiLaki->value,
    ]);
    $user->switchTeam($team);

    $laki = Santri::factory()->create([
        'team_id' => $team->id,
        'nis' => null,
        'gender' => SantriGender::LakiLaki->value,
        'status' => SantriStatus::Pendaftar->value,
    ]);
    Santri::factory()->create([
        'team_id' => $team->id,
        'nis' => null,
        'gender' => SantriGender::Perempuan->value,
        'status' => SantriStatus::Pendaftar->value,
    ]);

    $this
        ->actingAs($user)
        ->get(route('admin.pendaftaran.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('pendaftarans.data', 1)
            ->where('pendaftarans.data.0.id', $laki->id)
            ->where('santriScope', SantriGender::LakiLaki->value));
});
