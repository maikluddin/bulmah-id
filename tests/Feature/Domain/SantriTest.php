<?php

use App\Enums\SantriGender;
use App\Enums\SantriStatus;
use App\Models\Santri;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\QueryException;

test('a santri can be created', function () {
    $santri = Santri::factory()->create();

    expect($santri)->toBeInstanceOf(Santri::class);
    $this->assertDatabaseHas('santris', ['id' => $santri->id]);
});

test('a santri defaults to aktif status', function () {
    $santri = Santri::factory()->create();

    expect($santri->status)->toBe(SantriStatus::Aktif);
    $this->assertDatabaseHas('santris', ['id' => $santri->id, 'status' => SantriStatus::Aktif->value]);
});

test('a santri belongs to a lembaga (team)', function () {
    $team = Team::factory()->create();
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    expect($santri->team->is($team))->toBeTrue();
});

test('a santri belongs to a wali (guardian) user account', function () {
    $wali = User::factory()->create();
    $santri = Santri::factory()->create(['wali_user_id' => $wali->id]);

    expect($santri->wali->is($wali))->toBeTrue();
});

test('a user can supervise multiple santri as wali', function () {
    $wali = User::factory()->create();
    $santris = Santri::factory()->count(3)->create(['wali_user_id' => $wali->id]);

    expect($wali->santris->pluck('id'))->toEqual($santris->pluck('id'));
});

test('nis must be unique within a lembaga', function () {
    $team = Team::factory()->create();
    Santri::factory()->create(['team_id' => $team->id, 'nis' => '2024.001']);

    $this->expectException(QueryException::class);

    Santri::factory()->create(['team_id' => $team->id, 'nis' => '2024.001']);
});

test('the same nis is allowed across different lembaga', function () {
    $first = Team::factory()->create();
    $second = Team::factory()->create();

    Santri::factory()->create(['team_id' => $first->id, 'nis' => '2024.001']);
    Santri::factory()->create(['team_id' => $second->id, 'nis' => '2024.001']);

    expect(Santri::where('nis', '2024.001')->count())->toBe(2);
});

test('aktif scope only returns aktif santri', function () {
    Santri::factory()->create();
    Santri::factory()->alumni()->create();

    expect(Santri::aktif()->count())->toBe(1);
});

test('a santri can be soft deleted', function () {
    $santri = Santri::factory()->create();

    $santri->delete();

    $this->assertSoftDeleted('santris', ['id' => $santri->id]);
    expect(Santri::find($santri->id))->toBeNull();
});

test('a lembaga has many santri', function () {
    $team = Team::factory()->create();
    $santris = Santri::factory()->count(2)->create(['team_id' => $team->id]);

    expect($team->santris->pluck('id'))->toEqual($santris->pluck('id'));
});

test('a santri can be created with a wali account via factory', function () {
    $santri = Santri::factory()->withWaliAccount()->create();

    expect($santri->wali)->toBeInstanceOf(User::class);
});

test('a santri gender is cast to the enum', function () {
    $santri = Santri::factory()->create(['gender' => SantriGender::LakiLaki]);

    expect($santri->gender)->toBe(SantriGender::LakiLaki);
});
