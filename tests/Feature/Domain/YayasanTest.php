<?php

use App\Models\Team;
use App\Models\Yayasan;

test('a yayasan can be created', function () {
    $yayasan = Yayasan::factory()->create();

    expect($yayasan)->toBeInstanceOf(Yayasan::class);
    $this->assertDatabaseHas('yayasans', ['id' => $yayasan->id]);
});

test('yayasan slug is auto generated from the name', function () {
    $yayasan = Yayasan::create(['name' => 'Yayasan Al-Bulmah']);

    expect($yayasan->slug)->toBe('yayasan-al-bulmah');
});

test('yayasan slug is unique', function () {
    $first = Yayasan::create(['name' => 'Yayasan Al-Bulmah']);
    $second = Yayasan::create(['name' => 'Yayasan Al-Bulmah']);

    expect($first->slug)->toBe('yayasan-al-bulmah');
    expect($second->slug)->toBe('yayasan-al-bulmah-1');
});

test('a yayasan has many lembaga', function () {
    $yayasan = Yayasan::factory()->create();
    $lembaga = Team::factory()->count(2)->create(['yayasan_id' => $yayasan->id]);

    expect($yayasan->lembaga->pluck('id'))->toEqual($lembaga->pluck('id'));
});

test('a yayasan can be soft deleted', function () {
    $yayasan = Yayasan::factory()->create();

    $yayasan->delete();

    $this->assertSoftDeleted('yayasans', ['id' => $yayasan->id]);
    expect(Yayasan::find($yayasan->id))->toBeNull();
});

test('force deleting a yayasan detaches its lembaga', function () {
    $yayasan = Yayasan::factory()->create();
    $lembaga = Team::factory()->create(['yayasan_id' => $yayasan->id]);

    $yayasan->forceDelete();

    expect($lembaga->refresh()->yayasan_id)->toBeNull();
});

test('a lembaga belongs to a yayasan', function () {
    $yayasan = Yayasan::factory()->create();
    $lembaga = Team::factory()->create(['yayasan_id' => $yayasan->id]);

    expect($lembaga->yayasan->is($yayasan))->toBeTrue();
});

test('a lembaga without a yayasan has a null yayasan relation', function () {
    $team = Team::factory()->create();

    expect($team->yayasan)->toBeNull();
});
