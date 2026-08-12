<?php

use App\Enums\SantriGender;
use App\Enums\TeamRole;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Santri;
use App\Models\Team;
use App\Models\User;
use App\Models\Yayasan;

test('the database seeder creates the yayasan and four lembaga', function () {
    $this->seed();

    expect(Yayasan::where('name', 'Yayasan Ponpes Bustanul Hikmah')->exists())->toBeTrue()
        ->and(Team::where('slug', 'pondok-bustanul-hikmah')->exists())->toBeTrue()
        ->and(Team::where('slug', 'smpi-bustanul-hikmah')->exists())->toBeTrue()
        ->and(Team::where('slug', 'smki-bustanul-hikmah')->exists())->toBeTrue()
        ->and(Team::where('slug', 'mau-bustanul-hikmah')->exists())->toBeTrue();
});

test('the database seeder creates the accounts with the correct roles', function () {
    $this->seed();

    $pondok = Team::where('slug', 'pondok-bustanul-hikmah')->first();

    $admin = User::where('email', 'admin@bulmah.id')->first();

    expect($admin->ownsTeam($pondok))->toBeTrue()
        ->and($admin->ownsTeam(Team::where('slug', 'smpi-bustanul-hikmah')->first()))->toBeTrue()
        ->and($admin->ownsTeam(Team::where('slug', 'smki-bustanul-hikmah')->first()))->toBeTrue()
        ->and($admin->ownsTeam(Team::where('slug', 'mau-bustanul-hikmah')->first()))->toBeTrue();

    $adminPutra = User::where('email', 'admin-putra@bulmah.id')->first();
    $adminPutri = User::where('email', 'admin-putri@bulmah.id')->first();

    expect($adminPutra->teamRole($pondok))->toBe(TeamRole::AdminLembaga)
        ->and($adminPutra->santriGenderFor($pondok))->toBe(SantriGender::LakiLaki)
        ->and($adminPutra->personalTeam())->not->toBeNull()
        ->and($adminPutri->teamRole($pondok))->toBe(TeamRole::AdminLembaga)
        ->and($adminPutri->santriGenderFor($pondok))->toBe(SantriGender::Perempuan);

    $adminLembaga = User::where('email', 'admin-smpi@bulmah.id')->first();

    expect($adminLembaga->teamRole(Team::where('slug', 'smpi-bustanul-hikmah')->first()))->toBe(TeamRole::AdminLembaga)
        ->and($adminLembaga->personalTeam())->not->toBeNull();

    expect(User::where('email', 'admin-smki@bulmah.id')->exists())->toBeTrue()
        ->and(User::where('email', 'admin-mau@bulmah.id')->exists())->toBeTrue();
});

test('the database seeder creates content for every lembaga', function () {
    $this->seed();

    foreach (Team::whereIn('slug', ['pondok-bustanul-hikmah', 'smpi-bustanul-hikmah', 'smki-bustanul-hikmah', 'mau-bustanul-hikmah'])->get() as $team) {
        expect(PostCategory::where('team_id', $team->id)->count())->toBe(3)
            ->and(Post::where('team_id', $team->id)->count())->toBe(3)
            ->and(Santri::where('team_id', $team->id)->count())->toBe(3);
    }
});

test('the database seeder is idempotent', function () {
    $this->seed();
    $this->seed();

    expect(Team::count())->toBe(16)
        ->and(PostCategory::count())->toBe(12)
        ->and(Post::count())->toBe(12)
        ->and(Santri::count())->toBe(12)
        ->and(User::count())->toBe(12);
});
