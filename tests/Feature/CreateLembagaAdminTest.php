<?php

use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

test('the command creates an admin lembaga account with a personal team', function () {
    $lembaga = Team::factory()->create(['name' => 'SMPI Bustanul Hikmah', 'slug' => 'smpi-bustanul-hikmah']);

    $this->artisan('lembaga:admin', [
        'team' => 'smpi-bustanul-hikmah',
        '--email' => 'admin-smpi@bulmah.id',
    ])->assertSuccessful();

    $user = User::where('email', 'admin-smpi@bulmah.id')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Admin SMPI Bustanul Hikmah')
        ->and($user->teamRole($lembaga))->toBe(TeamRole::AdminLembaga)
        ->and($user->isCurrentTeam($lembaga))->toBeTrue()
        ->and($user->personalTeam())->not->toBeNull()
        ->and($user->hasTeamPermission($lembaga, TeamPermission::ManagePosts))->toBeTrue()
        ->and($user->hasTeamPermission($lembaga, TeamPermission::AddMember))->toBeFalse();
});

test('the command defaults the email from the lembaga slug', function () {
    Team::factory()->create(['name' => 'SMKI Bustanul Hikmah', 'slug' => 'smki-bustanul-hikmah']);

    $this->artisan('lembaga:admin', [
        'team' => 'smki-bustanul-hikmah',
    ])->assertSuccessful();

    $this->assertDatabaseHas('users', ['email' => 'admin-smki@bulmah.id']);
});

test('the command is idempotent', function () {
    $lembaga = Team::factory()->create(['name' => 'MAU Bustanul Hikmah', 'slug' => 'mau-bustanul-hikmah']);

    $this->artisan('lembaga:admin', [
        'team' => 'mau-bustanul-hikmah',
        '--email' => 'admin-mau@bulmah.id',
    ])->assertSuccessful();

    $this->artisan('lembaga:admin', [
        'team' => 'mau-bustanul-hikmah',
        '--email' => 'admin-mau@bulmah.id',
    ])->assertSuccessful();

    $user = User::where('email', 'admin-mau@bulmah.id')->first();

    expect(User::where('email', 'admin-mau@bulmah.id')->count())->toBe(1)
        ->and($user->teamMemberships()->count())->toBe(2)
        ->and($user->teamRole($lembaga))->toBe(TeamRole::AdminLembaga);
});

test('the command fails when the lembaga does not exist', function () {
    $this->artisan('lembaga:admin', [
        'team' => 'tidak-ada',
    ])->assertFailed();
});

test('the command fails for a personal team', function () {
    $user = User::factory()->create();

    $this->artisan('lembaga:admin', [
        'team' => $user->personalTeam()->slug,
    ])->assertFailed();
});

test('an admin lembaga logs in and lands on the lembaga dashboard', function () {
    $lembaga = Team::factory()->create(['name' => 'SMPI Bustanul Hikmah', 'slug' => 'smpi-bustanul-hikmah']);

    $this->artisan('lembaga:admin', [
        'team' => 'smpi-bustanul-hikmah',
        '--email' => 'admin-smpi@bulmah.id',
        '--password' => 'password',
    ])->assertSuccessful();

    $response = $this->post(route('login.store'), [
        'email' => 'admin-smpi@bulmah.id',
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/smpi-bustanul-hikmah/dashboard');

    $this->get(route('dashboard', ['current_team' => 'smpi-bustanul-hikmah']))
        ->assertOk();
});
