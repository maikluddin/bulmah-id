<?php

use App\Enums\PembayaranJenis;
use App\Enums\PembayaranMetode;
use App\Enums\PembayaranStatus;
use App\Enums\TeamRole;
use App\Models\Pembayaran;
use App\Models\Santri;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('unauthenticated users are redirected to login from the pembayaran page', function () {
    $team = Team::factory()->create();
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->get(route('admin.santri.pembayaran.index', [$team, $santri]))
        ->assertRedirectToRoute('login');
});

test('a team owner can view the pembayaran records of a santri', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

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
        ->get(route('admin.santri.pembayaran.index', [$team, $santri]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/pembayaran/index')
            ->has('pembayarans.data', 2)
            ->where('total_lunas', 250000)
            ->where('total_pending', 150000)
            ->where('santri.id', $santri->id));
});

test('a team owner can record a pembayaran for a santri', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->from(route('admin.santri.pembayaran.index', [$team, $santri]))
        ->post(route('admin.santri.pembayaran.store', [$team, $santri]), [
            'jenis' => PembayaranJenis::Spp->value,
            'jumlah' => 300000,
            'tanggal' => '2026-08-10',
            'metode' => PembayaranMetode::Transfer->value,
            'status' => PembayaranStatus::Lunas->value,
            'keterangan' => 'SPP bulan Agustus',
        ])
        ->assertRedirect(route('admin.santri.pembayaran.index', [$team, $santri]));

    $pembayaran = Pembayaran::firstOrFail();

    expect($pembayaran->santri_id)->toBe($santri->id)
        ->and($pembayaran->team_id)->toBe($team->id)
        ->and($pembayaran->recorded_by)->toBe($user->id)
        ->and($pembayaran->jenis)->toBe(PembayaranJenis::Spp)
        ->and((float) $pembayaran->jumlah)->toBe(300000.0);
});

test('a team owner can delete a pembayaran record', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);
    $pembayaran = Pembayaran::factory()->create(['team_id' => $team->id, 'santri_id' => $santri->id]);

    $this
        ->actingAs($user)
        ->from(route('admin.santri.pembayaran.index', [$team, $santri]))
        ->delete(route('admin.santri.pembayaran.destroy', [$team, $santri, $pembayaran]))
        ->assertRedirect(route('admin.santri.pembayaran.index', [$team, $santri]));

    expect(Pembayaran::find($pembayaran->id))->toBeNull();
});

test('a pembayaran belonging to another santri cannot be deleted', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);
    $otherSantri = Santri::factory()->create(['team_id' => $team->id]);
    $pembayaran = Pembayaran::factory()->create(['team_id' => $team->id, 'santri_id' => $otherSantri->id]);

    $this
        ->actingAs($user)
        ->delete(route('admin.santri.pembayaran.destroy', [$team, $santri, $pembayaran]))
        ->assertNotFound();

    expect(Pembayaran::find($pembayaran->id))->not->toBeNull();
});

test('an admin pembayaran can view the pembayaran records of a santri', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::AdminPembayaran->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    Pembayaran::factory()->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'status' => PembayaranStatus::Lunas,
        'jumlah' => 250000,
    ]);

    $this
        ->actingAs($user)
        ->get(route('admin.santri.pembayaran.index', [$team, $santri]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/pembayaran/index')
            ->has('pembayarans.data', 1)
            ->where('total_lunas', 250000)
            ->where('santri.id', $santri->id));
});

test('an admin pembayaran can record a pembayaran for a santri', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::AdminPembayaran->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->from(route('admin.santri.pembayaran.index', [$team, $santri]))
        ->post(route('admin.santri.pembayaran.store', [$team, $santri]), [
            'jenis' => PembayaranJenis::Spp->value,
            'jumlah' => 300000,
            'tanggal' => '2026-08-10',
            'metode' => PembayaranMetode::Transfer->value,
            'status' => PembayaranStatus::Lunas->value,
            'keterangan' => 'SPP bulan Agustus',
        ])
        ->assertRedirect(route('admin.santri.pembayaran.index', [$team, $santri]));

    $pembayaran = Pembayaran::firstOrFail();

    expect($pembayaran->santri_id)->toBe($santri->id)
        ->and($pembayaran->recorded_by)->toBe($user->id);
});

test('an ustaz cannot manage pembayaran records', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Ustaz->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->post(route('admin.santri.pembayaran.store', [$team, $santri]), [
            'jenis' => PembayaranJenis::Spp->value,
            'jumlah' => 100000,
            'tanggal' => '2026-08-10',
            'metode' => PembayaranMetode::Tunai->value,
            'status' => PembayaranStatus::Lunas->value,
        ])
        ->assertForbidden();

    expect(Pembayaran::count())->toBe(0);
});
