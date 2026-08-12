<?php

use App\Enums\HafalanType;
use App\Enums\TeamRole;
use App\Models\Hafalan;
use App\Models\Santri;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('unauthenticated users are redirected to login from the hafalan page', function () {
    $team = Team::factory()->create();
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->get(route('admin.santri.hafalan.index', [$team, $santri]))
        ->assertRedirectToRoute('login');
});

test('a team owner can view the hafalan records of a santri', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    Hafalan::factory()->count(2)->hafalanBaru()->create(['team_id' => $team->id, 'santri_id' => $santri->id]);

    $this
        ->actingAs($user)
        ->get(route('admin.santri.hafalan.index', [$team, $santri]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/hafalan/index')
            ->has('hafalans.data', 2)
            ->where('santri.id', $santri->id)
            ->where('filters.search', '')
            ->where('filters.type', ''));
});

test('a team owner can record a hafalan for a santri', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->post(route('admin.santri.hafalan.store', [$team, $santri]), [
            'type' => HafalanType::Murajaah->value,
            'juz' => 1,
            'surat' => 'Al-Fatihah',
            'ayat_from' => 1,
            'ayat_to' => 7,
            'tanggal' => '2026-08-10',
            'keterangan' => 'Lancar',
        ])
        ->assertRedirect(route('admin.santri.hafalan.index', [$team, $santri]));

    $hafalan = Hafalan::firstOrFail();

    expect($hafalan->santri_id)->toBe($santri->id)
        ->and($hafalan->team_id)->toBe($team->id)
        ->and($hafalan->ustad_user_id)->toBe($user->id)
        ->and($hafalan->type)->toBe(HafalanType::Murajaah)
        ->and($hafalan->surat)->toBe('Al-Fatihah');
});

test('the surat is required when recording a hafalan', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->post(route('admin.santri.hafalan.store', [$team, $santri]), [
            'type' => HafalanType::HafalanBaru->value,
            'tanggal' => '2026-08-10',
        ])
        ->assertSessionHasErrors('surat');

    expect(Hafalan::count())->toBe(0);
});

test('a team owner can delete a hafalan record', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);
    $hafalan = Hafalan::factory()->create(['team_id' => $team->id, 'santri_id' => $santri->id]);

    $this
        ->actingAs($user)
        ->delete(route('admin.santri.hafalan.destroy', [$team, $santri, $hafalan]))
        ->assertRedirect(route('admin.santri.hafalan.index', [$team, $santri]));

    expect(Hafalan::find($hafalan->id))->toBeNull();
});

test('a hafalan belonging to another santri cannot be deleted', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);
    $otherSantri = Santri::factory()->create(['team_id' => $team->id]);
    $hafalan = Hafalan::factory()->create(['team_id' => $team->id, 'santri_id' => $otherSantri->id]);

    $this
        ->actingAs($user)
        ->delete(route('admin.santri.hafalan.destroy', [$team, $santri, $hafalan]))
        ->assertNotFound();

    expect(Hafalan::find($hafalan->id))->not->toBeNull();
});

test('an ustaz cannot manage hafalan records', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Ustaz->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->get(route('admin.santri.hafalan.index', [$team, $santri]))
        ->assertForbidden();
});
