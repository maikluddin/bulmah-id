<?php

use App\Enums\TeamRole;
use App\Models\Hafalan;
use App\Models\Santri;
use App\Models\Team;
use App\Models\User;
use App\Notifications\HafalanSetoranNotification;
use Inertia\Testing\AssertableInertia as Assert;

function waliUser(): User
{
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);
    $user->switchTeam($team);

    return $user;
}

function waliTeam(User $user): Team
{
    return $user->teams()->where('is_personal', false)->firstOrFail();
}

test('a wali can view the portal and see their santri', function () {
    $user = waliUser();
    $team = waliTeam($user);

    $santri = Santri::factory()->create([
        'team_id' => $team->id,
        'wali_user_id' => $user->id,
    ]);

    Hafalan::factory()->hafalanBaru()->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'surat' => 'Al-Baqarah',
        'juz' => 2,
    ]);

    $this
        ->actingAs($user)
        ->get(route('wali.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('wali/index')
            ->has('santris', 1)
            ->where('santris.0.id', $santri->id)
            ->where('santris.0.summary.total_hafalan', 1)
            ->where('santris.0.summary.hafalan_baru', 1)
            ->has('santris.0.recent', 1)
            ->where('santris.0.recent.0.surat', 'Al-Baqarah'));
});

test('a wali only sees their own santri in the current team', function () {
    $user = waliUser();
    $team = waliTeam($user);
    $otherTeam = Team::factory()->create();

    $mine = Santri::factory()->create([
        'team_id' => $team->id,
        'wali_user_id' => $user->id,
    ]);
    Santri::factory()->create([
        'team_id' => $team->id,
        'wali_user_id' => User::factory()->create()->id,
    ]);
    Santri::factory()->create([
        'team_id' => $otherTeam->id,
        'wali_user_id' => $user->id,
    ]);

    $this
        ->actingAs($user)
        ->get(route('wali.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('santris', 1)
            ->where('santris.0.id', $mine->id));
});

test('a user without a linked santri in the team cannot access the wali portal', function () {
    $user = waliUser();
    $team = waliTeam($user);

    $this
        ->actingAs($user)
        ->get(route('wali.index', $team))
        ->assertForbidden();
});

test('the wali portal shows setoran notifications', function () {
    $user = waliUser();
    $team = waliTeam($user);

    $santri = Santri::factory()->create([
        'team_id' => $team->id,
        'wali_user_id' => $user->id,
    ]);

    $hafalan = Hafalan::factory()->hafalanBaru()->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'surat' => 'Al-Ikhlas',
    ]);

    $user->notify(new HafalanSetoranNotification($hafalan));

    $this
        ->actingAs($user)
        ->get(route('wali.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('notifications', 1)
            ->where('notifications.0.data.surat', 'Al-Ikhlas'));
});

test('a wali without a santri in the current team cannot access the portal', function () {
    $user = waliUser();
    $team = waliTeam($user);
    $otherTeam = Team::factory()->create();

    Santri::factory()->create([
        'team_id' => $otherTeam->id,
        'wali_user_id' => $user->id,
    ]);

    $this
        ->actingAs($user)
        ->get(route('wali.index', $team))
        ->assertForbidden();
});
