<?php

use App\Enums\LembagaJenjang;
use App\Enums\LembagaType;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('a team owner can view the lembaga index', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this
        ->actingAs($user)
        ->get(route('admin.lembaga.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/lembaga/index')
            ->has('lembagas', 1)
            ->where('lembagas.0.slug', $team->slug)
            ->has('lembagas.0.posts_count')
            ->has('lembagas.0.santris_count'));
});

test('the lembaga index aggregates lembaga across owned teams for an owner', function () {
    $user = User::factory()->create();
    $teamA = Team::factory()->create();
    $teamB = Team::factory()->create();

    $user->teams()->attach($teamA, ['role' => TeamRole::Owner->value]);
    $user->teams()->attach($teamB, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($teamA);

    $this
        ->actingAs($user)
        ->get(route('admin.lembaga.index', $teamA))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('lembagas', 2)
            ->where('isAggregate', true));
});

test('a member without the lembaga permission cannot view the lembaga index', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Ustaz->value]);

    $this
        ->actingAs($user)
        ->get(route('admin.lembaga.index', $team))
        ->assertForbidden();
});

test('a team owner can view the lembaga profile', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this
        ->actingAs($user)
        ->get(route('admin.lembaga.edit', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/lembaga/profile')
            ->where('team.slug', $team->slug));
});

test('a team owner can update the lembaga profile', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this
        ->actingAs($user)
        ->put(route('admin.lembaga.update', $team), [
            'type' => LembagaType::PondokPesantren->value,
            'description' => 'Pesantren modern berbasis Al-Quran.',
            'address' => 'Jl. Pesantren No. 10, Bandung',
            'phone' => '022-1234567',
            'email' => 'info@pesantren.test',
            'yayasan_id' => null,
        ])
        ->assertRedirect()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Profil lembaga berhasil diperbarui.']);

    $team->refresh();

    expect($team->type)->toBe(LembagaType::PondokPesantren->value)
        ->and($team->email)->toBe('info@pesantren.test');
});

test('an invalid email is rejected', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this
        ->actingAs($user)
        ->put(route('admin.lembaga.update', $team), [
            'email' => 'bukan-email',
        ])
        ->assertSessionHasErrors('email');
});

test('the lembaga profile of a personal team is forbidden', function () {
    $user = User::factory()->create();
    $personalTeam = $user->personalTeam();

    $this
        ->actingAs($user)
        ->get(route('admin.lembaga.edit', $personalTeam))
        ->assertForbidden();
});

test('an admin can manage the lembaga profile', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Admin->value]);

    $this
        ->actingAs($user)
        ->put(route('admin.lembaga.update', $team), [
            'description' => 'Deskripsi baru.',
        ])
        ->assertRedirect();

    expect($team->fresh()->description)->toBe('Deskripsi baru.');
});

test('a member without the lembaga permission cannot update the profile', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Ustaz->value]);

    $this
        ->actingAs($user)
        ->put(route('admin.lembaga.update', $team), [
            'description' => 'Tidak boleh.',
        ])
        ->assertForbidden();
});

test('the lembaga profile exposes the current jenjang and options', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['jenjang' => LembagaJenjang::SMPI->value]);
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this
        ->actingAs($user)
        ->get(route('admin.lembaga.edit', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/lembaga/profile')
            ->where('team.jenjang', 'smpi')
            ->where('team.jenjang_label', 'SMP Islam')
            ->has('jenjangs', 4));
});

test('an admin can update the lembaga jenjang', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Admin->value]);

    $this
        ->actingAs($user)
        ->from(route('admin.lembaga.edit', $team))
        ->put(route('admin.lembaga.update', $team), [
            'jenjang' => LembagaJenjang::MAU->value,
        ])
        ->assertRedirect(route('admin.lembaga.edit', $team))
        ->assertSessionHasNoErrors();

    expect($team->fresh()->jenjang)->toBe(LembagaJenjang::MAU->value);
});

test('the lembaga profile rejects an invalid jenjang', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this
        ->actingAs($user)
        ->from(route('admin.lembaga.edit', $team))
        ->put(route('admin.lembaga.update', $team), [
            'jenjang' => 'universitas',
        ])
        ->assertSessionHasErrors('jenjang');
});
