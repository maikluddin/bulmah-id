<?php

use App\Enums\AbsensiStatus;
use App\Enums\TeamRole;
use App\Models\Absensi;
use App\Models\Santri;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('unauthenticated users are redirected to login from the absensi page', function () {
    $team = Team::factory()->create();
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->get(route('admin.santri.absensi.index', [$team, $santri]))
        ->assertRedirectToRoute('login');
});

test('a team owner can view the absensi records of a santri', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    Absensi::factory()->hadir()->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'tanggal' => now()->startOfMonth()->format('Y-m-d'),
    ]);
    Absensi::factory()->alpa()->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'tanggal' => now()->startOfMonth()->addDay()->format('Y-m-d'),
    ]);

    $this
        ->actingAs($user)
        ->get(route('admin.santri.absensi.index', [$team, $santri]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/absensi/index')
            ->has('records', 2)
            ->where('stats.hadir', 1)
            ->where('stats.alpa', 1)
            ->where('santri.id', $santri->id));
});

test('the absensi records are scoped to the selected month', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    Absensi::factory()->hadir()->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'tanggal' => '2026-06-10',
    ]);
    Absensi::factory()->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'tanggal' => '2026-07-10',
        'status' => AbsensiStatus::Sakit,
    ]);

    $this
        ->actingAs($user)
        ->get(route('admin.santri.absensi.index', [$team, $santri, 'month' => '2026-06']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('month', '2026-06')
            ->has('records', 1)
            ->where('stats.hadir', 1)
            ->where('stats.sakit', 0));
});

test('a team owner can record an absensi for a santri', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->from(route('admin.santri.absensi.index', [$team, $santri]))
        ->post(route('admin.santri.absensi.store', [$team, $santri]), [
            'tanggal' => '2026-08-10',
            'status' => AbsensiStatus::Izin->value,
            'keterangan' => 'Acara keluarga',
        ])
        ->assertRedirect(route('admin.santri.absensi.index', [$team, $santri]));

    $absensi = Absensi::firstOrFail();

    expect($absensi->santri_id)->toBe($santri->id)
        ->and($absensi->team_id)->toBe($team->id)
        ->and($absensi->status)->toBe(AbsensiStatus::Izin);
});

test('recording an absensi on an existing date updates the record', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    Absensi::factory()->hadir()->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'tanggal' => '2026-08-10',
    ]);

    $this
        ->actingAs($user)
        ->from(route('admin.santri.absensi.index', [$team, $santri]))
        ->post(route('admin.santri.absensi.store', [$team, $santri]), [
            'tanggal' => '2026-08-10',
            'status' => AbsensiStatus::Alpa->value,
        ])
        ->assertRedirect(route('admin.santri.absensi.index', [$team, $santri]));

    expect(Absensi::where('santri_id', $santri->id)->count())->toBe(1)
        ->and(Absensi::where('santri_id', $santri->id)->first()->status)->toBe(AbsensiStatus::Alpa);
});

test('a team owner can delete an absensi record', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);
    $absensi = Absensi::factory()->create(['team_id' => $team->id, 'santri_id' => $santri->id]);

    $this
        ->actingAs($user)
        ->from(route('admin.santri.absensi.index', [$team, $santri]))
        ->delete(route('admin.santri.absensi.destroy', [$team, $santri, $absensi]))
        ->assertRedirect(route('admin.santri.absensi.index', [$team, $santri]));

    expect(Absensi::find($absensi->id))->toBeNull();
});

test('an ustaz cannot manage absensi records', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Ustaz->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->post(route('admin.santri.absensi.store', [$team, $santri]), [
            'tanggal' => '2026-08-10',
            'status' => AbsensiStatus::Hadir->value,
        ])
        ->assertForbidden();

    expect(Absensi::count())->toBe(0);
});
