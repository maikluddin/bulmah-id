<?php

use App\Enums\SantriGender;
use App\Enums\SantriStatus;
use App\Models\Santri;
use App\Models\Team;
use Inertia\Testing\AssertableInertia as Assert;

test('guests can view the public registration form', function () {
    $lembaga = Team::factory()->create(['name' => 'Pondok Al-Bulmah']);

    $this->get(route('daftar.create', $lembaga))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/pendaftaran-create')
            ->where('lembaga.name', 'Pondok Al-Bulmah')
            ->has('genders', 2));
});

test('the registration form is not available for personal teams', function () {
    $personal = Team::factory()->personal()->create();

    $this->get(route('daftar.create', $personal))->assertNotFound();
});

test('a guest can submit the registration form as a pendaftar', function () {
    $lembaga = Team::factory()->create(['name' => 'Pondok Al-Bulmah']);

    $this->post(route('daftar.store', $lembaga), [
        'nisn' => '0012345678',
        'name' => 'Ahmad Fauzi',
        'gender' => SantriGender::LakiLaki->value,
        'birth_place' => 'Bandung',
        'birth_date' => '2012-05-12',
        'address' => 'Jl. Merdeka No. 1',
        'address_province' => 'JAWA BARAT',
        'address_city' => 'BANDUNG',
        'address_district' => 'COBLONG',
        'address_village' => 'SADANG SERANG',
        'wali_name' => 'H. Udin',
        'wali_phone' => '081234567890',
    ])->assertRedirect(route('daftar.create', $lembaga));

    $santri = Santri::firstOrFail();

    expect($santri->team_id)->toBe($lembaga->id)
        ->and($santri->status)->toBe(SantriStatus::Pendaftar)
        ->and($santri->nis)->toBeNull()
        ->and($santri->address_province)->toBe('JAWA BARAT')
        ->and($santri->address_city)->toBe('BANDUNG')
        ->and($santri->address_district)->toBe('COBLONG')
        ->and($santri->address_village)->toBe('SADANG SERANG');
});

test('the registration form validates required fields', function () {
    $lembaga = Team::factory()->create();

    $this->post(route('daftar.store', $lembaga), [
        'name' => '',
        'gender' => '',
        'address_province' => '',
        'address_city' => '',
        'address_district' => '',
        'address_village' => '',
        'address' => '',
        'wali_name' => '',
        'wali_phone' => '',
    ])->assertSessionHasErrors([
        'name',
        'gender',
        'address_province',
        'address_city',
        'address_district',
        'address_village',
        'address',
        'wali_name',
        'wali_phone',
    ]);

    expect(Santri::count())->toBe(0);
});

test('a nisn must be unique within the same lembaga', function () {
    $lembaga = Team::factory()->create();
    $lembaga->santris()->create([
        'nisn' => '0012345678',
        'name' => 'Santri Lama',
        'gender' => SantriGender::LakiLaki->value,
        'status' => SantriStatus::Pendaftar->value,
    ]);

    $this->post(route('daftar.store', $lembaga), [
        'nisn' => '0012345678',
        'name' => 'Santri Baru',
        'gender' => SantriGender::Perempuan->value,
        'address_province' => 'JAWA BARAT',
        'address_city' => 'BANDUNG',
        'address_district' => 'COBLONG',
        'address_village' => 'SADANG SERANG',
        'address' => 'Jl. Merdeka No. 1',
        'wali_name' => 'Wali Baru',
        'wali_phone' => '081234567890',
    ])->assertSessionHasErrors('nisn');
});
