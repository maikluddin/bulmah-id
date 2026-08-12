<?php

use App\Enums\PembayaranStatus;
use App\Enums\SantriGender;
use App\Enums\TeamRole;
use App\Models\Pembayaran;
use App\Models\Santri;
use App\Models\SantriWallet;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('unauthenticated users are redirected to login from the keuangan page', function () {
    $team = Team::factory()->create();

    $this
        ->get(route('admin.keuangan.index', $team))
        ->assertRedirectToRoute('login');
});

test('an admin pembayaran can view the keuangan overview', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::AdminPembayaran->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);
    SantriWallet::factory()->create(['santri_id' => $santri->id, 'balance' => 25000]);

    Pembayaran::factory()->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'status' => PembayaranStatus::Lunas,
        'jumlah' => 250000,
    ]);
    Pembayaran::factory()->pending()->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'jumlah' => 150000,
    ]);

    $this
        ->actingAs($user)
        ->get(route('admin.keuangan.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/keuangan/index')
            ->has('santris.data', 1)
            ->where('summary.total_santri', 1)
            ->where('summary.saldo_dompet', 25000)
            ->where('summary.pembayaran_lunas', 250000)
            ->where('summary.pembayaran_pending', 150000)
            ->where('santris.data.0.saldo', 25000)
            ->where('santris.data.0.pembayaran_lunas', 250000)
            ->where('santris.data.0.pembayaran_pending', 150000));
});

test('the keuangan overview respects the gender scope', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::AdminPembayaran->value, 'santri_gender' => SantriGender::LakiLaki]);
    Santri::factory()->create(['team_id' => $team->id, 'gender' => SantriGender::LakiLaki]);
    Santri::factory()->create(['team_id' => $team->id, 'gender' => SantriGender::Perempuan]);

    $this
        ->actingAs($user)
        ->get(route('admin.keuangan.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.total_santri', 1)
            ->has('santris.data', 1));
});

test('an ustaz cannot access the keuangan page', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Ustaz->value]);

    $this
        ->actingAs($user)
        ->get(route('admin.keuangan.index', $team))
        ->assertForbidden();
});

test('a member cannot access the keuangan page', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $this
        ->actingAs($user)
        ->get(route('admin.keuangan.index', $team))
        ->assertForbidden();
});
