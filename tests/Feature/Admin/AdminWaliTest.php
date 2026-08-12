<?php

use App\Enums\TeamRole;
use App\Models\Santri;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('unauthenticated users are redirected to login', function () {
    $team = Team::factory()->create();

    $this->get(route('admin.wali.index', $team))->assertRedirectToRoute('login');
});

test('a team owner can view the wali index', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Santri::factory()->count(2)->withWaliAccount()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->get(route('admin.wali.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/wali/index')
            ->has('walis.data', 2)
            ->where('team.slug', $team->slug));
});

test('a team owner can create a wali account', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $response = $this
        ->actingAs($user)
        ->post(route('admin.wali.store', $team), [
            'name' => 'H. Muhammad Ridwan',
            'email' => 'wali-baru@example.test',
            'phone' => '081234567890',
            'password' => 'secret-password',
            'santri_ids' => [$santri->id],
        ]);

    $wali = User::where('email', 'wali-baru@example.test')->firstOrFail();

    $response->assertRedirect(route('admin.wali.edit', [$team, $wali]));

    expect($wali->teamRole($team))->toBe(TeamRole::Member)
        ->and($wali->santris()->count())->toBe(1)
        ->and($wali->personalTeam())->not->toBeNull();

    $santri->refresh();

    expect($santri->wali_user_id)->toBe($wali->id)
        ->and($santri->wali_name)->toBe('H. Muhammad Ridwan')
        ->and($santri->wali_phone)->toBe('081234567890');
});

test('creating a wali account requires at least one santri', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this
        ->actingAs($user)
        ->post(route('admin.wali.store', $team), [
            'name' => 'H. Muhammad Ridwan',
            'email' => 'wali-baru@example.test',
            'password' => 'secret-password',
            'santri_ids' => [],
        ])
        ->assertSessionHasErrors('santri_ids');

    expect(User::where('email', 'wali-baru@example.test')->exists())->toBeFalse();
});

test('the wali email must be unique', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    User::factory()->create(['email' => 'wali-baru@example.test']);

    $this
        ->actingAs($user)
        ->post(route('admin.wali.store', $team), [
            'name' => 'Wali Lain',
            'email' => 'wali-baru@example.test',
            'password' => 'secret-password',
            'santri_ids' => [$santri->id],
        ])
        ->assertSessionHasErrors('email');
});

test('a santri from another team cannot be linked', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $otherSantri = Santri::factory()->create(['team_id' => $otherTeam->id]);

    $this
        ->actingAs($user)
        ->post(route('admin.wali.store', $team), [
            'name' => 'H. Muhammad Ridwan',
            'email' => 'wali-baru@example.test',
            'password' => 'secret-password',
            'santri_ids' => [$otherSantri->id],
        ])
        ->assertSessionHasErrors('santri_ids.0');
});

test('a team owner can update a wali account', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santriA = Santri::factory()->create(['team_id' => $team->id]);
    $santriB = Santri::factory()->create(['team_id' => $team->id]);
    $wali = User::factory()->create();
    $santriA->update(['wali_user_id' => $wali->id, 'wali_name' => $wali->name]);

    $this
        ->actingAs($user)
        ->put(route('admin.wali.update', [$team, $wali]), [
            'name' => 'H. Muhammad Ridwan Baru',
            'email' => $wali->email,
            'phone' => '085711223344',
            'password' => '',
            'santri_ids' => [$santriA->id, $santriB->id],
        ])
        ->assertRedirect(route('admin.wali.edit', [$team, $wali]));

    $santriA->refresh();
    $santriB->refresh();

    expect($wali->fresh()->name)->toBe('H. Muhammad Ridwan Baru')
        ->and($santriA->wali_name)->toBe('H. Muhammad Ridwan Baru')
        ->and($santriA->wali_phone)->toBe('085711223344')
        ->and($santriB->wali_user_id)->toBe($wali->id);
});

test('leaving the password blank does not change it', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);
    $wali = User::factory()->create(['password' => 'original-password']);
    $santri->update(['wali_user_id' => $wali->id, 'wali_name' => $wali->name]);

    $this
        ->actingAs($user)
        ->put(route('admin.wali.update', [$team, $wali]), [
            'name' => $wali->name,
            'email' => $wali->email,
            'password' => '',
            'santri_ids' => [$santri->id],
        ])
        ->assertRedirect(route('admin.wali.edit', [$team, $wali]));

    expect(Hash::check('original-password', $wali->fresh()->password))->toBeTrue();
});

test('a team owner can update the wali password', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);
    $wali = User::factory()->create(['password' => 'original-password']);
    $santri->update(['wali_user_id' => $wali->id, 'wali_name' => $wali->name]);

    $this
        ->actingAs($user)
        ->put(route('admin.wali.update', [$team, $wali]), [
            'name' => $wali->name,
            'email' => $wali->email,
            'password' => 'new-password',
            'santri_ids' => [$santri->id],
        ])
        ->assertRedirect(route('admin.wali.edit', [$team, $wali]));

    expect(Hash::check('new-password', $wali->fresh()->password))->toBeTrue();
});

test('a team owner can unlink santri when updating a wali account', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santriA = Santri::factory()->create(['team_id' => $team->id]);
    $santriB = Santri::factory()->create(['team_id' => $team->id]);
    $wali = User::factory()->create();
    $santriA->update(['wali_user_id' => $wali->id, 'wali_name' => $wali->name]);
    $santriB->update(['wali_user_id' => $wali->id, 'wali_name' => $wali->name]);

    $this
        ->actingAs($user)
        ->put(route('admin.wali.update', [$team, $wali]), [
            'name' => $wali->name,
            'email' => $wali->email,
            'password' => '',
            'santri_ids' => [$santriA->id],
        ])
        ->assertRedirect(route('admin.wali.edit', [$team, $wali]));

    expect($santriA->fresh()->wali_user_id)->toBe($wali->id)
        ->and($santriB->fresh()->wali_user_id)->toBeNull();
});

test('a team owner can delete a wali account', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);
    $wali = User::factory()->create();
    $santri->update(['wali_user_id' => $wali->id, 'wali_name' => $wali->name]);

    $this
        ->actingAs($user)
        ->delete(route('admin.wali.destroy', [$team, $wali]))
        ->assertRedirect(route('admin.wali.index', $team));

    expect($santri->fresh()->wali_user_id)->toBeNull()
        ->and($user->belongsToTeam($team))->toBeTrue()
        ->and(User::find($wali->id))->toBeNull();
});

test('deleting a wali keeps the account when it belongs to another team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);
    $wali = User::factory()->create();
    $santri->update(['wali_user_id' => $wali->id, 'wali_name' => $wali->name]);
    $otherTeam->members()->attach($wali, ['role' => TeamRole::Member->value]);

    $this
        ->actingAs($user)
        ->delete(route('admin.wali.destroy', [$team, $wali]))
        ->assertRedirect(route('admin.wali.index', $team));

    expect($wali->fresh())->not->toBeNull()
        ->and($user->belongsToTeam($team))->toBeTrue()
        ->and($wali->belongsToTeam($otherTeam))->toBeTrue()
        ->and($wali->belongsToTeam($team))->toBeFalse();
});

test('a wali account from another team cannot be edited', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $otherSantri = Santri::factory()->create(['team_id' => $otherTeam->id]);
    $wali = User::factory()->create();
    $otherSantri->update(['wali_user_id' => $wali->id, 'wali_name' => $wali->name]);

    $this
        ->actingAs($user)
        ->get(route('admin.wali.edit', [$team, $wali]))
        ->assertNotFound();
});

test('an ustaz cannot manage wali accounts', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Ustaz->value]);

    $this
        ->actingAs($user)
        ->get(route('admin.wali.index', $team))
        ->assertForbidden();
});

test('a member without the wali permission cannot create a wali account', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->post(route('admin.wali.store', $team), [
            'name' => 'H. Muhammad Ridwan',
            'email' => 'wali-baru@example.test',
            'password' => 'secret-password',
            'santri_ids' => [$santri->id],
        ])
        ->assertForbidden();

    expect(User::where('email', 'wali-baru@example.test')->exists())->toBeFalse();
});

test('a member without the wali permission cannot delete a wali account', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);
    $wali = User::factory()->create();
    $santri->update(['wali_user_id' => $wali->id, 'wali_name' => $wali->name]);

    $this
        ->actingAs($user)
        ->delete(route('admin.wali.destroy', [$team, $wali]))
        ->assertForbidden();

    expect($santri->fresh()->wali_user_id)->toBe($wali->id);
});

test('the wali index can be searched by name', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $waliA = User::factory()->create(['name' => 'H. Ahmad Fauzi']);
    $waliB = User::factory()->create(['name' => 'Hj. Siti Aminah']);

    Santri::factory()->create(['team_id' => $team->id, 'wali_user_id' => $waliA->id]);
    Santri::factory()->create(['team_id' => $team->id, 'wali_user_id' => $waliB->id]);

    $this
        ->actingAs($user)
        ->get(route('admin.wali.index', [$team, 'search' => 'Ahmad']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('walis.data', 1)
            ->where('filters.search', 'Ahmad'));
});
