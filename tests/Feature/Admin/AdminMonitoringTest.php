<?php

use App\Enums\HafalanKategoriJenis;
use App\Enums\PembayaranStatus;
use App\Enums\PerizinanStatus;
use App\Enums\TeamRole;
use App\Models\Absensi;
use App\Models\Hafalan;
use App\Models\HafalanKategori;
use App\Models\Pembayaran;
use App\Models\Perizinan;
use App\Models\Santri;
use App\Models\SantriWallet;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('unauthenticated users are redirected to login from the monitoring page', function () {
    $team = Team::factory()->create();
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->get(route('admin.santri.monitoring', [$team, $santri]))
        ->assertRedirectToRoute('login');
});

test('a team owner can view the monitoring page for a santri', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    Hafalan::factory()->count(3)->create(['team_id' => $team->id, 'santri_id' => $santri->id]);
    Absensi::factory()->hadir()->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'tanggal' => now()->startOfMonth()->format('Y-m-d'),
    ]);
    Absensi::factory()->hadir()->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'tanggal' => now()->startOfMonth()->addDay()->format('Y-m-d'),
    ]);
    SantriWallet::factory()->create(['santri_id' => $santri->id, 'balance' => 50000]);
    Pembayaran::factory()->create(['team_id' => $team->id, 'santri_id' => $santri->id, 'status' => PembayaranStatus::Lunas, 'jumlah' => 300000]);
    Perizinan::factory()->create(['team_id' => $team->id, 'santri_id' => $santri->id, 'status' => PerizinanStatus::Pending]);

    $this
        ->actingAs($user)
        ->get(route('admin.santri.monitoring', [$team, $santri]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/monitoring/show')
            ->where('santri.name', $santri->name)
            ->where('santri.team.slug', $team->slug)
            ->where('summary.total_hafalan', 3)
            ->where('summary.total_absensi', 2)
            ->where('summary.absensi_hadir', 2)
            ->where('summary.saldo', 50000)
            ->where('summary.pembayaran_lunas', 300000)
            ->where('summary.perizinan_pending', 1)
            ->where('month', now()->format('Y-m')));
});

test('the monitoring summary is scoped to the selected month for absensi and hafalan', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    Hafalan::factory()->count(1)->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'tanggal' => '2026-06-05',
    ]);
    Hafalan::factory()->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'tanggal' => '2026-07-05',
    ]);
    Absensi::factory()->hadir()->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'tanggal' => '2026-06-10',
    ]);
    Absensi::factory()->alpa()->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'tanggal' => '2026-07-10',
    ]);

    $this
        ->actingAs($user)
        ->get(route('admin.santri.monitoring', [$team, $santri, 'month' => '2026-06']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('month', '2026-06')
            ->where('summary.total_hafalan', 2)
            ->where('summary.hafalan_bulan_ini', 1)
            ->where('summary.absensi_hadir', 1)
            ->where('summary.absensi_alpa', 0)
            ->where('summary.total_absensi', 2));
});

test('an ustaz cannot view the monitoring page', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Ustaz->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->get(route('admin.santri.monitoring', [$team, $santri]))
        ->assertForbidden();
});

test('a santri belonging to another team cannot be monitored', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $otherTeam->id]);

    $this
        ->actingAs($user)
        ->get(route('admin.santri.monitoring', [$team, $santri]))
        ->assertNotFound();
});

test('monitoring includes per-kategori hafalan progress for the santri', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);
    $otherSantri = Santri::factory()->create(['team_id' => $team->id]);

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

    Hafalan::factory()->count(2)->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'kategori_id' => $juzAmma->id,
    ]);
    Hafalan::factory()->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'kategori_id' => $alfatih->id,
    ]);
    Hafalan::factory()->count(5)->create([
        'team_id' => $team->id,
        'santri_id' => $otherSantri->id,
        'kategori_id' => $juzAmma->id,
    ]);

    $this
        ->actingAs($user)
        ->get(route('admin.santri.monitoring', [$team, $santri]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('kategoriProgress', 2)
            ->where('kategoriProgress.0.nama', 'Juz Amma')
            ->where('kategoriProgress.0.jenis_label', 'Al-Qur\'an')
            ->where('kategoriProgress.0.jumlah', 2)
            ->where('kategoriProgress.1.nama', 'Kitab Alfatih')
            ->where('kategoriProgress.1.jumlah', 1));
});
