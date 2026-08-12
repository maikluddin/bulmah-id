<?php

use App\Enums\HafalanKategoriJenis;
use App\Enums\LembagaJenjang;
use App\Enums\TeamRole;
use App\Models\Hafalan;
use App\Models\HafalanKategori;
use App\Models\Santri;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('unauthenticated users are redirected to login from the kategori hafalan page', function () {
    $team = Team::factory()->create();

    $this
        ->get(route('admin.hafalan-kategori.index', $team))
        ->assertRedirectToRoute('login');
});

test('a team owner can view the kategori hafalan page', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    HafalanKategori::factory()->create([
        'team_id' => $team->id,
        'jenis' => HafalanKategoriJenis::Alquran,
        'nama' => 'Juz Amma',
    ]);
    HafalanKategori::factory()->create([
        'team_id' => $team->id,
        'jenis' => HafalanKategoriJenis::Kitab,
        'nama' => 'Kitab Alfatih',
    ]);

    $this
        ->actingAs($user)
        ->get(route('admin.hafalan-kategori.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/hafalan-kategori/index')
            ->has('kategoris', 2)
            ->where('kategoris.0.jenis', 'alquran')
            ->where('kategoris.0.jenis_label', 'Al-Qur\'an')
            ->where('kategoris.1.jenis', 'kitab')
            ->where('kategoris.1.jenis_label', 'Kitab')
            ->has('jenisOptions', 2));
});

test('an ustaz cannot access the kategori hafalan page', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Ustaz->value]);

    $this
        ->actingAs($user)
        ->get(route('admin.hafalan-kategori.index', $team))
        ->assertForbidden();
});

test('kategori hafalan are scoped to the team and jenis', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    HafalanKategori::factory()->create([
        'team_id' => $team->id,
        'jenis' => HafalanKategoriJenis::Alquran,
        'nama' => 'Juz Amma',
    ]);
    HafalanKategori::factory()->create([
        'team_id' => $otherTeam->id,
        'jenis' => HafalanKategoriJenis::Alquran,
        'nama' => 'Juz Amma',
    ]);

    $this
        ->actingAs($user)
        ->get(route('admin.hafalan-kategori.index', $team, ['jenis' => 'alquran']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('kategoris', 1)
            ->where('kategoris.0.nama', 'Juz Amma'));
});

test('an owner can create a hafalan kategori with a jenis', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this
        ->actingAs($user)
        ->from(route('admin.hafalan-kategori.index', $team))
        ->post(route('admin.hafalan-kategori.store', $team), [
            'jenis' => HafalanKategoriJenis::Kitab->value,
            'nama' => 'Kitab Imrithi',
        ])
        ->assertRedirect(route('admin.hafalan-kategori.index', $team));

    $this->assertDatabaseHas('hafalan_kategoris', [
        'team_id' => $team->id,
        'jenis' => HafalanKategoriJenis::Kitab->value,
        'nama' => 'Kitab Imrithi',
    ]);
});

test('a kategori hafalan name must be unique within a team and jenis', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    HafalanKategori::factory()->create([
        'team_id' => $team->id,
        'jenis' => HafalanKategoriJenis::Alquran,
        'nama' => 'Juz Amma',
    ]);

    $this
        ->actingAs($user)
        ->post(route('admin.hafalan-kategori.store', $team), [
            'jenis' => HafalanKategoriJenis::Alquran->value,
            'nama' => 'Juz Amma',
        ])
        ->assertSessionHasErrors('nama');
});

test('an owner can update a hafalan kategori', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $kategori = HafalanKategori::factory()->create([
        'team_id' => $team->id,
        'jenis' => HafalanKategoriJenis::Alquran,
        'nama' => 'Juz Amma',
    ]);

    $this
        ->actingAs($user)
        ->from(route('admin.hafalan-kategori.index', $team))
        ->put(route('admin.hafalan-kategori.update', [$team, $kategori]), [
            'jenis' => HafalanKategoriJenis::Kitab->value,
            'nama' => 'Alfiyah',
        ])
        ->assertRedirect(route('admin.hafalan-kategori.index', $team));

    $this->assertDatabaseHas('hafalan_kategoris', [
        'id' => $kategori->id,
        'jenis' => HafalanKategoriJenis::Kitab->value,
        'nama' => 'Alfiyah',
    ]);
});

test('a kategori hafalan with hafalan records cannot be deleted', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $kategori = HafalanKategori::factory()->create(['team_id' => $team->id]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);
    Hafalan::factory()->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'kategori_id' => $kategori->id,
    ]);

    $this
        ->actingAs($user)
        ->delete(route('admin.hafalan-kategori.destroy', [$team, $kategori]))
        ->assertSessionHasErrors('kategori');

    $this->assertDatabaseHas('hafalan_kategoris', ['id' => $kategori->id]);
});

test('an owner can delete a hafalan kategori without records', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $kategori = HafalanKategori::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->from(route('admin.hafalan-kategori.index', $team))
        ->delete(route('admin.hafalan-kategori.destroy', [$team, $kategori]))
        ->assertRedirect(route('admin.hafalan-kategori.index', $team));

    $this->assertDatabaseMissing('hafalan_kategoris', ['id' => $kategori->id]);
});

test('the kategori hafalan page exposes the template categories for the lembaga jenjang', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['jenjang' => LembagaJenjang::SMPI->value]);
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this
        ->actingAs($user)
        ->get(route('admin.hafalan-kategori.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/hafalan-kategori/index')
            ->where('jenjang', 'smpi')
            ->has('templateKategoris', 3)
            ->where('templateKategoris.0.jenis', 'alquran')
            ->where('templateKategoris.0.nama', 'Juz Amma')
            ->where('templateKategoris.1.nama', 'Jilid Yanbua')
            ->where('templateKategoris.2.jenis', 'kitab')
            ->where('templateKategoris.2.nama', 'Kitab Alfatih'));
});

test('an owner can store the template categories for the lembaga jenjang', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['jenjang' => LembagaJenjang::SMPI->value]);
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this
        ->actingAs($user)
        ->from(route('admin.hafalan-kategori.index', $team))
        ->post(route('admin.hafalan-kategori.template', $team))
        ->assertRedirect(route('admin.hafalan-kategori.index', $team));

    $this->assertDatabaseHas('hafalan_kategoris', [
        'team_id' => $team->id,
        'jenis' => HafalanKategoriJenis::Alquran->value,
        'nama' => 'Juz Amma',
    ]);
    $this->assertDatabaseHas('hafalan_kategoris', [
        'team_id' => $team->id,
        'jenis' => HafalanKategoriJenis::Kitab->value,
        'nama' => 'Kitab Alfatih',
    ]);
    $this->assertDatabaseCount('hafalan_kategoris', 3);
});

test('storing the template does not duplicate existing categories', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['jenjang' => LembagaJenjang::SMPI->value]);
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    HafalanKategori::factory()->create([
        'team_id' => $team->id,
        'jenis' => HafalanKategoriJenis::Alquran,
        'nama' => 'Juz Amma',
    ]);

    $this
        ->actingAs($user)
        ->post(route('admin.hafalan-kategori.template', $team))
        ->assertRedirect();

    $this->assertDatabaseCount('hafalan_kategoris', 3);
});

test('an ustaz cannot store the template categories', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['jenjang' => LembagaJenjang::SMPI->value]);
    $team->members()->attach($user, ['role' => TeamRole::Ustaz->value]);

    $this
        ->actingAs($user)
        ->post(route('admin.hafalan-kategori.template', $team))
        ->assertForbidden();

    $this->assertDatabaseCount('hafalan_kategoris', 0);
});
