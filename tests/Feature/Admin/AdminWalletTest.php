<?php

use App\Enums\TeamRole;
use App\Enums\WalletTransactionType;
use App\Models\Santri;
use App\Models\SantriWallet;
use App\Models\Team;
use App\Models\User;
use App\Models\WalletTransaction;
use Inertia\Testing\AssertableInertia as Assert;

test('unauthenticated users are redirected to login from the wallet page', function () {
    $team = Team::factory()->create();
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->get(route('admin.santri.wallet.index', [$team, $santri]))
        ->assertRedirectToRoute('login');
});

test('a team owner can view the wallet of a santri', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);
    SantriWallet::factory()->create(['santri_id' => $santri->id, 'balance' => 25000]);

    WalletTransaction::factory()->setor()->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'amount' => 25000,
    ]);

    $this
        ->actingAs($user)
        ->get(route('admin.santri.wallet.index', [$team, $santri]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/wallet/index')
            ->has('transactions.data', 1)
            ->where('balance', 25000)
            ->where('santri.id', $santri->id));
});

test('a team owner can record a setoran and it increases the wallet balance', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->from(route('admin.santri.wallet.index', [$team, $santri]))
        ->post(route('admin.santri.wallet.store', [$team, $santri]), [
            'type' => WalletTransactionType::Setor->value,
            'amount' => 100000,
            'tanggal' => '2026-08-10',
            'description' => 'Tabungan mingguan',
        ])
        ->assertRedirect(route('admin.santri.wallet.index', [$team, $santri]));

    expect((float) $santri->wallet->balance)->toBe(100000.0)
        ->and(WalletTransaction::count())->toBe(1);
});

test('a team owner can record a penarikan and it decreases the wallet balance', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);
    SantriWallet::factory()->create(['santri_id' => $santri->id, 'balance' => 100000]);

    $this
        ->actingAs($user)
        ->from(route('admin.santri.wallet.index', [$team, $santri]))
        ->post(route('admin.santri.wallet.store', [$team, $santri]), [
            'type' => WalletTransactionType::Penarikan->value,
            'amount' => 40000,
            'tanggal' => '2026-08-10',
        ])
        ->assertRedirect(route('admin.santri.wallet.index', [$team, $santri]));

    expect((float) $santri->wallet->fresh()->balance)->toBe(60000.0);
});

test('a penarikan exceeding the wallet balance is rejected', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);
    SantriWallet::factory()->create(['santri_id' => $santri->id, 'balance' => 50000]);

    $this
        ->actingAs($user)
        ->from(route('admin.santri.wallet.index', [$team, $santri]))
        ->post(route('admin.santri.wallet.store', [$team, $santri]), [
            'type' => WalletTransactionType::Penarikan->value,
            'amount' => 60000,
            'tanggal' => '2026-08-10',
        ])
        ->assertSessionHasErrors('amount');

    expect(WalletTransaction::count())->toBe(0)
        ->and((float) $santri->wallet->balance)->toBe(50000.0);
});

test('a team owner can delete a transaction and the balance is reversed', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);
    SantriWallet::factory()->create(['santri_id' => $santri->id, 'balance' => 100000]);
    $transaction = WalletTransaction::factory()->setor()->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'amount' => 100000,
    ]);

    $this
        ->actingAs($user)
        ->from(route('admin.santri.wallet.index', [$team, $santri]))
        ->delete(route('admin.santri.wallet.destroy', [$team, $santri, $transaction]))
        ->assertRedirect(route('admin.santri.wallet.index', [$team, $santri]));

    expect(WalletTransaction::find($transaction->id))->toBeNull()
        ->and((float) $santri->wallet->fresh()->balance)->toBe(0.0);
});

test('an admin pembayaran can manage the wallet', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::AdminPembayaran->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->from(route('admin.santri.wallet.index', [$team, $santri]))
        ->post(route('admin.santri.wallet.store', [$team, $santri]), [
            'type' => WalletTransactionType::Setor->value,
            'amount' => 50000,
            'tanggal' => '2026-08-10',
        ])
        ->assertRedirect(route('admin.santri.wallet.index', [$team, $santri]));

    expect((float) $santri->wallet->fresh()->balance)->toBe(50000.0);
});

test('an ustaz cannot manage the wallet', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Ustaz->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->post(route('admin.santri.wallet.store', [$team, $santri]), [
            'type' => WalletTransactionType::Setor->value,
            'amount' => 10000,
            'tanggal' => '2026-08-10',
        ])
        ->assertForbidden();

    expect(WalletTransaction::count())->toBe(0);
});
