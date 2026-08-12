<?php

use App\Enums\SantriGender;
use App\Enums\SantriStatus;
use App\Enums\TeamRole;
use App\Models\Santri;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('unauthenticated users are redirected to login', function () {
    $team = Team::factory()->create();

    $this->get(route('admin.santri.index', $team))->assertRedirectToRoute('login');
});

test('a team owner can view the santri index', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Santri::factory()->count(3)->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->get(route('admin.santri.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/santri/index')
            ->has('santris.data', 3)
            ->where('team.slug', $team->slug));
});

test('the santri index aggregates santri across owned lembaga for an owner', function () {
    $user = User::factory()->create();
    $teamA = Team::factory()->create();
    $teamB = Team::factory()->create();

    $user->teams()->attach($teamA, ['role' => TeamRole::Owner->value]);
    $user->teams()->attach($teamB, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($teamA);

    Santri::factory()->create(['team_id' => $teamA->id, 'gender' => SantriGender::LakiLaki]);
    Santri::factory()->create(['team_id' => $teamB->id, 'gender' => SantriGender::Perempuan]);

    $this
        ->actingAs($user)
        ->get(route('admin.santri.index', $teamA))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('santris.data', 2)
            ->where('santriScope', null)
            ->where('isAggregate', true)
            ->has('santris.data.0.team.slug'));
});

test('a team owner can create a santri', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $response = $this
        ->actingAs($user)
        ->post(route('admin.santri.store', $team), [
            'nis' => '2026001',
            'nisn' => '0012345678',
            'name' => 'Ahmad Fauzi',
            'gender' => SantriGender::LakiLaki->value,
            'birth_place' => 'Bandung',
            'birth_date' => '2010-05-12',
            'address' => 'Jl. Merdeka No. 1',
            'status' => SantriStatus::Aktif->value,
            'wali_name' => 'H. Udin',
            'wali_phone' => '081234567890',
            'entry_date' => '2026-07-01',
            'exit_date' => null,
        ]);

    $santri = Santri::firstOrFail();

    $response->assertRedirect(route('admin.santri.edit', [$team, $santri]));

    expect($santri->team_id)->toBe($team->id)
        ->and($santri->name)->toBe('Ahmad Fauzi');
});

test('a team owner can update a santri', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->put(route('admin.santri.update', [$team, $santri]), [
            'nis' => $santri->nis,
            'name' => 'Ahmad Fauzi Albar',
            'gender' => $santri->gender->value,
            'status' => SantriStatus::Alumni->value,
            'exit_date' => '2026-08-01',
        ])
        ->assertRedirect(route('admin.santri.edit', [$team, $santri]));

    $santri->refresh();

    expect($santri->name)->toBe('Ahmad Fauzi Albar')
        ->and($santri->status)->toBe(SantriStatus::Alumni);
});

test('the nis must be unique within the same team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $existing = Santri::factory()->create(['team_id' => $team->id, 'nis' => '2026001']);

    $this
        ->actingAs($user)
        ->post(route('admin.santri.store', $team), [
            'nis' => '2026001',
            'name' => 'Santri Lain',
            'gender' => SantriGender::Perempuan->value,
            'status' => SantriStatus::Aktif->value,
        ])
        ->assertSessionHasErrors('nis');

    expect($existing->fresh()->name)->toBe($existing->name);
});

test('the same nis is allowed across different teams', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Santri::factory()->create(['team_id' => $otherTeam->id, 'nis' => '2026001']);

    $this
        ->actingAs($user)
        ->post(route('admin.santri.store', $team), [
            'nis' => '2026001',
            'name' => 'Santri Baru',
            'gender' => SantriGender::LakiLaki->value,
            'status' => SantriStatus::Aktif->value,
        ])
        ->assertRedirect();

    expect(Santri::where('team_id', $team->id)->where('nis', '2026001')->exists())->toBeTrue();
});

test('a santri belonging to another team cannot be edited', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $otherTeam->id]);

    $this
        ->actingAs($user)
        ->get(route('admin.santri.edit', [$team, $santri]))
        ->assertNotFound();
});

test('a team owner can delete a santri', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->delete(route('admin.santri.destroy', [$team, $santri]))
        ->assertRedirect(route('admin.santri.index', $team))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Santri berhasil dihapus.']);

    $this->assertSoftDeleted('santris', ['id' => $santri->id]);
});

test('an ustaz cannot manage santri', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Ustaz->value]);

    $this
        ->actingAs($user)
        ->get(route('admin.santri.index', $team))
        ->assertForbidden();
});

test('the santri index can be filtered by status', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Santri::factory()->count(2)->create(['team_id' => $team->id]);
    Santri::factory()->count(3)->alumni()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->get(route('admin.santri.index', [$team, 'status' => SantriStatus::Alumni->value]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('santris.data', 3)
            ->where('filters.status', SantriStatus::Alumni->value));
});

test('a member without the santri permission cannot create a santri', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $this
        ->actingAs($user)
        ->post(route('admin.santri.store', $team), [
            'nis' => '2026001',
            'name' => 'Anak Baru',
            'gender' => SantriGender::LakiLaki->value,
            'status' => SantriStatus::Aktif->value,
        ])
        ->assertForbidden();

    expect(Santri::count())->toBe(0);
});

test('a member without the santri permission cannot update a santri', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->put(route('admin.santri.update', [$team, $santri]), [
            'nis' => $santri->nis,
            'name' => 'Diubah',
            'gender' => $santri->gender->value,
            'status' => $santri->status->value,
        ])
        ->assertForbidden();
});

test('a member without the santri permission cannot delete a santri', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->delete(route('admin.santri.destroy', [$team, $santri]))
        ->assertForbidden();

    $this->assertNotSoftDeleted('santris', ['id' => $santri->id]);
});

test('a santri belonging to another team cannot be updated', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $otherTeam->id]);

    $this
        ->actingAs($user)
        ->put(route('admin.santri.update', [$team, $santri]), [
            'nis' => $santri->nis,
            'name' => 'Diubah',
            'gender' => $santri->gender->value,
            'status' => $santri->status->value,
        ])
        ->assertNotFound();
});

test('a santri belonging to another team cannot be deleted', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $otherTeam->id]);

    $this
        ->actingAs($user)
        ->delete(route('admin.santri.destroy', [$team, $santri]))
        ->assertNotFound();

    $this->assertNotSoftDeleted('santris', ['id' => $santri->id]);
});

test('a team owner can upload a photo when creating a santri', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $response = $this
        ->actingAs($user)
        ->post(route('admin.santri.store', $team), [
            'nis' => '2026002',
            'name' => 'Budi Santoso',
            'gender' => SantriGender::LakiLaki->value,
            'status' => SantriStatus::Aktif->value,
            'photo' => UploadedFile::fake()->image('foto.jpg'),
        ]);

    $santri = Santri::firstOrFail();

    $response->assertRedirect(route('admin.santri.edit', [$team, $santri]));

    Storage::disk('public')->assertExists($santri->photo_path);

    $this
        ->actingAs($user)
        ->get(route('admin.santri.edit', [$team, $santri]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('santri.photo_path', $santri->photo_path)
            ->where('santri.photo_url', Storage::disk('public')->url($santri->photo_path)));
});

test('a team owner can replace a photo when updating a santri', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $santri = Santri::factory()->create([
        'team_id' => $team->id,
        'photo_path' => 'santri/lama.jpg',
    ]);

    Storage::disk('public')->put('santri/lama.jpg', 'lama');

    $this
        ->actingAs($user)
        ->put(route('admin.santri.update', [$team, $santri]), [
            'nis' => $santri->nis,
            'name' => $santri->name,
            'gender' => $santri->gender->value,
            'status' => $santri->status->value,
            'photo' => UploadedFile::fake()->image('baru.jpg'),
        ])
        ->assertRedirect(route('admin.santri.edit', [$team, $santri]));

    $santri->refresh();

    Storage::disk('public')->assertMissing('santri/lama.jpg');
    Storage::disk('public')->assertExists($santri->photo_path);
});

test('a team owner can remove a photo when updating a santri', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $santri = Santri::factory()->create([
        'team_id' => $team->id,
        'photo_path' => 'santri/lama.jpg',
    ]);

    Storage::disk('public')->put('santri/lama.jpg', 'lama');

    $this
        ->actingAs($user)
        ->put(route('admin.santri.update', [$team, $santri]), [
            'nis' => $santri->nis,
            'name' => $santri->name,
            'gender' => $santri->gender->value,
            'status' => $santri->status->value,
            'remove_photo' => true,
        ])
        ->assertRedirect(route('admin.santri.edit', [$team, $santri]));

    Storage::disk('public')->assertMissing('santri/lama.jpg');
    expect($santri->fresh()->photo_path)->toBeNull();
});

test('deleting a santri removes its photo file', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $santri = Santri::factory()->create([
        'team_id' => $team->id,
        'photo_path' => 'santri/lama.jpg',
    ]);

    Storage::disk('public')->put('santri/lama.jpg', 'lama');

    $this
        ->actingAs($user)
        ->delete(route('admin.santri.destroy', [$team, $santri]))
        ->assertRedirect(route('admin.santri.index', $team));

    Storage::disk('public')->assertMissing('santri/lama.jpg');
});

test('a photo must be a valid image', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this
        ->actingAs($user)
        ->post(route('admin.santri.store', $team), [
            'nis' => '2026003',
            'name' => 'Santri Tanpa Foto',
            'gender' => SantriGender::LakiLaki->value,
            'status' => SantriStatus::Aktif->value,
            'photo' => UploadedFile::fake()->create('bukan-foto.txt', 100),
        ])
        ->assertSessionHasErrors('photo');

    expect(Santri::count())->toBe(0);
});
