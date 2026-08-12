<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

test('a user stuck on their personal team is redirected to a lembaga route', function () {
    $user = User::factory()->create();
    $personalTeam = $user->personalTeam();
    $lembaga = Team::factory()->create();
    $lembaga->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this
        ->actingAs($user)
        ->get(route('admin.lembaga.index', $personalTeam))
        ->assertRedirect('/'.$lembaga->slug.'/lembaga');
});

test('the redirected lembaga route becomes the current team', function () {
    $user = User::factory()->create();
    $lembaga = Team::factory()->create();
    $lembaga->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this
        ->actingAs($user)
        ->get('/'.$lembaga->slug.'/lembaga')
        ->assertSuccessful();

    expect($user->fresh()->current_team_id)->toBe($lembaga->id);
});

test('a user with only a personal team can still open their team-scoped pages', function () {
    $user = User::factory()->create();
    $personalTeam = $user->personalTeam();

    $this
        ->actingAs($user)
        ->get(route('dashboard', $personalTeam))
        ->assertSuccessful();
});

test('a user who owns lembaga is never redirected away from a lembaga route', function () {
    $user = User::factory()->create();
    $lembaga = Team::factory()->create();
    $lembaga->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($lembaga);

    $this
        ->actingAs($user)
        ->get(route('admin.lembaga.index', $lembaga))
        ->assertSuccessful();
});
