<?php

use App\Enums\PerizinanJenis;
use App\Enums\PerizinanStatus;
use App\Enums\TeamRole;
use App\Models\Perizinan;
use App\Models\Santri;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('unauthenticated users are redirected to login from the perizinan page', function () {
    $team = Team::factory()->create();
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->get(route('admin.santri.perizinan.index', [$team, $santri]))
        ->assertRedirectToRoute('login');
});

test('a team owner can view the perizinan records of a santri', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    Perizinan::factory()->create(['team_id' => $team->id, 'santri_id' => $santri->id]);
    Perizinan::factory()->disetujui()->create(['team_id' => $team->id, 'santri_id' => $santri->id]);

    $this
        ->actingAs($user)
        ->get(route('admin.santri.perizinan.index', [$team, $santri]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/perizinan/index')
            ->has('perizinans.data', 2)
            ->where('pendingCount', 1)
            ->where('santri.id', $santri->id));
});

test('a team owner can record a perizinan for a santri', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->from(route('admin.santri.perizinan.index', [$team, $santri]))
        ->post(route('admin.santri.perizinan.store', [$team, $santri]), [
            'jenis' => PerizinanJenis::IzinPulang->value,
            'alasan' => 'Pulang ke rumah orang tua',
            'tanggal_mulai' => '2026-08-15',
            'tanggal_selesai' => '2026-08-17',
            'catatan' => null,
        ])
        ->assertRedirect(route('admin.santri.perizinan.index', [$team, $santri]));

    $perizinan = Perizinan::firstOrFail();

    expect($perizinan->santri_id)->toBe($santri->id)
        ->and($perizinan->team_id)->toBe($team->id)
        ->and($perizinan->jenis)->toBe(PerizinanJenis::IzinPulang)
        ->and($perizinan->status)->toBe(PerizinanStatus::Pending);
});

test('a team owner can approve a pending perizinan', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);
    $perizinan = Perizinan::factory()->create(['team_id' => $team->id, 'santri_id' => $santri->id]);

    $this
        ->actingAs($user)
        ->from(route('admin.santri.perizinan.index', [$team, $santri]))
        ->patch(route('admin.santri.perizinan.update', [$team, $santri, $perizinan]), [
            'status' => PerizinanStatus::Disetujui->value,
            'catatan' => 'Diizinkan',
        ])
        ->assertRedirect(route('admin.santri.perizinan.index', [$team, $santri]));

    expect($perizinan->fresh()->status)->toBe(PerizinanStatus::Disetujui);
});

test('the perizinan status cannot be reset to pending', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);
    $perizinan = Perizinan::factory()->disetujui()->create(['team_id' => $team->id, 'santri_id' => $santri->id]);

    $this
        ->actingAs($user)
        ->from(route('admin.santri.perizinan.index', [$team, $santri]))
        ->patch(route('admin.santri.perizinan.update', [$team, $santri, $perizinan]), [
            'status' => PerizinanStatus::Pending->value,
        ])
        ->assertSessionHasErrors('status');

    expect($perizinan->fresh()->status)->toBe(PerizinanStatus::Disetujui);
});

test('a team owner can delete a perizinan record', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);
    $perizinan = Perizinan::factory()->create(['team_id' => $team->id, 'santri_id' => $santri->id]);

    $this
        ->actingAs($user)
        ->from(route('admin.santri.perizinan.index', [$team, $santri]))
        ->delete(route('admin.santri.perizinan.destroy', [$team, $santri, $perizinan]))
        ->assertRedirect(route('admin.santri.perizinan.index', [$team, $santri]));

    expect(Perizinan::find($perizinan->id))->toBeNull();
});

test('an ustaz cannot manage perizinan records', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Ustaz->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->post(route('admin.santri.perizinan.store', [$team, $santri]), [
            'jenis' => PerizinanJenis::IzinKeluar->value,
            'alasan' => 'Acara keluarga',
            'tanggal_mulai' => '2026-08-15',
        ])
        ->assertForbidden();

    expect(Perizinan::count())->toBe(0);
});
