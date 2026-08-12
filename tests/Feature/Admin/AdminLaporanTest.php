<?php

use App\Enums\HafalanKategoriJenis;
use App\Enums\TeamRole;
use App\Models\Hafalan;
use App\Models\HafalanKategori;
use App\Models\Santri;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('unauthenticated users are redirected to login from the laporan setoran page', function () {
    $team = Team::factory()->create();

    $this
        ->get(route('admin.laporan.setoran', $team))
        ->assertRedirectToRoute('login');
});

test('an ustaz cannot access the laporan setoran page', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Ustaz->value]);

    $this
        ->actingAs($user)
        ->get(route('admin.laporan.setoran', $team))
        ->assertForbidden();
});

test('an owner can view the laporan setoran page with scoped records and summary', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $juzAmma = HafalanKategori::factory()->create([
        'team_id' => $team->id,
        'jenis' => HafalanKategoriJenis::Alquran,
        'nama' => 'Juz Amma',
    ]);
    $santri = Santri::factory()->create([
        'team_id' => $team->id,
        'name' => 'Ahmad Fauzi',
        'nis' => '20240001',
    ]);

    Hafalan::factory()->count(3)->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'kategori_id' => $juzAmma->id,
        'tanggal' => now()->startOfMonth()->format('Y-m-d'),
    ]);

    $otherTeam = Team::factory()->create();
    $otherSantri = Santri::factory()->create(['team_id' => $otherTeam->id]);
    Hafalan::factory()->count(4)->create([
        'team_id' => $otherTeam->id,
        'santri_id' => $otherSantri->id,
        'tanggal' => now()->startOfMonth()->format('Y-m-d'),
    ]);

    $this
        ->actingAs($user)
        ->get(route('admin.laporan.setoran', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/laporan/setoran')
            ->where('summary.total', 3)
            ->where('summary.santri', 1)
            ->where('summary.kategori_terpakai', 1)
            ->has('records.data', 3)
            ->where('records.data.0.santri.name', 'Ahmad Fauzi')
            ->has('perKategori', 1)
            ->where('perKategori.0.nama', 'Juz Amma')
            ->where('perKategori.0.jumlah', 3)
            ->has('perSantri', 1)
            ->where('perSantri.0.name', 'Ahmad Fauzi'));
});

test('the laporan setoran page respects the date range filter', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    Hafalan::factory()->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'tanggal' => '2026-06-10',
    ]);
    Hafalan::factory()->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'tanggal' => '2026-07-10',
    ]);

    $this
        ->actingAs($user)
        ->get(route('admin.laporan.setoran', [$team, 'from' => '2026-06-01', 'to' => '2026-06-30']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.total', 1)
            ->where('filters.from', '2026-06-01')
            ->where('filters.to', '2026-06-30'));
});

test('the laporan setoran page respects the kategori filter', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $juzAmma = HafalanKategori::factory()->create([
        'team_id' => $team->id,
        'jenis' => HafalanKategoriJenis::Alquran,
        'nama' => 'Juz Amma',
    ]);
    $alfatih = HafalanKategori::factory()->create([
        'team_id' => $team->id,
        'jenis' => HafalanKategoriJenis::Kitab,
        'nama' => 'Kitab Alfatih',
    ]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    Hafalan::factory()->count(2)->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'kategori_id' => $juzAmma->id,
        'tanggal' => now()->startOfMonth()->format('Y-m-d'),
    ]);
    Hafalan::factory()->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'kategori_id' => $alfatih->id,
        'tanggal' => now()->startOfMonth()->format('Y-m-d'),
    ]);

    $this
        ->actingAs($user)
        ->get(route('admin.laporan.setoran', [$team, 'kategori' => $juzAmma->id]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.total', 2)
            ->has('perKategori', 1)
            ->where('perKategori.0.nama', 'Juz Amma')
            ->where('perKategori.0.jumlah', 2));
});
