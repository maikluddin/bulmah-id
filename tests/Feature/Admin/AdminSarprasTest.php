<?php

use App\Enums\PerbaikanStatus;
use App\Enums\SarprasKategori;
use App\Enums\SarprasKondisi;
use App\Enums\TeamRole;
use App\Models\PerbaikanSarpras;
use App\Models\SarprasItem;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('unauthenticated users are redirected to login from the sarpras page', function () {
    $team = Team::factory()->create();

    $this
        ->get(route('admin.sarpras.index', $team))
        ->assertRedirectToRoute('login');
});

test('an admin lembaga can view the sarpras inventory', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::AdminLembaga->value]);
    $item = SarprasItem::factory()->create([
        'team_id' => $team->id,
        'nama' => 'Proyektor',
        'kategori' => SarprasKategori::RuangKelas,
        'jumlah' => 3,
        'kondisi' => SarprasKondisi::Baik,
    ]);
    PerbaikanSarpras::factory()->create([
        'team_id' => $team->id,
        'sarpras_item_id' => $item->id,
        'status' => PerbaikanStatus::Pending,
    ]);

    $this
        ->actingAs($user)
        ->get(route('admin.sarpras.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/sarpras/index')
            ->has('items', 1)
            ->where('items.0.nama', 'Proyektor')
            ->where('items.0.kategori', 'ruang_kelas')
            ->where('items.0.kondisi', 'baik')
            ->where('items.0.perbaikans_aktif', 1)
            ->where('summary.total_items', 1)
            ->where('summary.total_jumlah', 3)
            ->where('summary.rusak_aktif', 1)
            ->where('summary.barang_rusak', 0)
            ->has('perbaikans', 1)
            ->where('perbaikans.0.status', 'pending'));
});

test('the sarpras summary counts broken items and active repairs', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::AdminLembaga->value]);
    SarprasItem::factory()->create([
        'team_id' => $team->id,
        'kondisi' => SarprasKondisi::RusakBerat,
    ]);
    $item = SarprasItem::factory()->create([
        'team_id' => $team->id,
        'kondisi' => SarprasKondisi::Baik,
    ]);
    PerbaikanSarpras::factory()->create([
        'team_id' => $team->id,
        'sarpras_item_id' => $item->id,
        'status' => PerbaikanStatus::Diproses,
    ]);
    PerbaikanSarpras::factory()->create([
        'team_id' => $team->id,
        'sarpras_item_id' => $item->id,
        'status' => PerbaikanStatus::Selesai,
    ]);

    $this
        ->actingAs($user)
        ->get(route('admin.sarpras.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.barang_rusak', 1)
            ->where('summary.rusak_aktif', 1));
});

test('the sarpras inventory only shows items from the current team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::AdminLembaga->value]);
    SarprasItem::factory()->create(['team_id' => $otherTeam->id]);

    $this
        ->actingAs($user)
        ->get(route('admin.sarpras.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('items', 0));
});

test('a user without the sarpras permission cannot access the page', function (TeamRole $role) {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => $role->value]);

    $this
        ->actingAs($user)
        ->get(route('admin.sarpras.index', $team))
        ->assertForbidden();
})->with([
    'admin pembayaran' => TeamRole::AdminPembayaran,
    'ustaz' => TeamRole::Ustaz,
    'member' => TeamRole::Member,
]);

test('an admin lembaga can create a sarpras item', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::AdminLembaga->value]);

    $this
        ->actingAs($user)
        ->from(route('admin.sarpras.index', $team))
        ->post(route('admin.sarpras.store', $team), [
            'nama' => 'Papan Tulis',
            'kategori' => SarprasKategori::RuangKelas->value,
            'lokasi' => 'Ruang 2',
            'jumlah' => 5,
            'kondisi' => SarprasKondisi::Baik->value,
            'keterangan' => 'Baru dibeli',
        ])
        ->assertRedirect(route('admin.sarpras.index', $team));

    $this->assertDatabaseHas('sarpras_items', [
        'team_id' => $team->id,
        'nama' => 'Papan Tulis',
        'jumlah' => 5,
    ]);
});

test('the sarpras item name must be unique within the team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::AdminLembaga->value]);
    SarprasItem::factory()->create(['team_id' => $team->id, 'nama' => 'Papan Tulis']);

    $this
        ->actingAs($user)
        ->from(route('admin.sarpras.index', $team))
        ->post(route('admin.sarpras.store', $team), [
            'nama' => 'Papan Tulis',
            'kategori' => SarprasKategori::RuangKelas->value,
            'jumlah' => 2,
            'kondisi' => SarprasKondisi::Baik->value,
        ])
        ->assertSessionHasErrors('nama');

    $this->assertDatabaseCount('sarpras_items', 1);
});

test('an admin lembaga can update a sarpras item', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::AdminLembaga->value]);
    $item = SarprasItem::factory()->create(['team_id' => $team->id, 'nama' => 'Meja']);

    $this
        ->actingAs($user)
        ->from(route('admin.sarpras.index', $team))
        ->put(route('admin.sarpras.update', [$team, $item]), [
            'nama' => 'Meja Kayu',
            'kategori' => SarprasKategori::RuangKelas->value,
            'jumlah' => 8,
            'kondisi' => SarprasKondisi::RusakRingan->value,
        ])
        ->assertRedirect(route('admin.sarpras.index', $team));

    $this->assertDatabaseHas('sarpras_items', [
        'id' => $item->id,
        'nama' => 'Meja Kayu',
        'jumlah' => 8,
        'kondisi' => SarprasKondisi::RusakRingan->value,
    ]);
});

test('an admin lembaga cannot update a sarpras item from another team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::AdminLembaga->value]);
    $item = SarprasItem::factory()->create(['team_id' => $otherTeam->id]);

    $this
        ->actingAs($user)
        ->put(route('admin.sarpras.update', [$team, $item]), [
            'nama' => 'Hack',
            'kategori' => SarprasKategori::Lainnya->value,
            'jumlah' => 1,
            'kondisi' => SarprasKondisi::Baik->value,
        ])
        ->assertNotFound();

    $this->assertDatabaseHas('sarpras_items', ['id' => $item->id, 'nama' => $item->nama]);
});

test('an admin lembaga can delete a sarpras item', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::AdminLembaga->value]);
    $item = SarprasItem::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->from(route('admin.sarpras.index', $team))
        ->delete(route('admin.sarpras.destroy', [$team, $item]))
        ->assertRedirect(route('admin.sarpras.index', $team));

    $this->assertDatabaseMissing('sarpras_items', ['id' => $item->id]);
});

test('an admin lembaga can upload a foto when creating a sarpras item', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::AdminLembaga->value]);

    $this
        ->actingAs($user)
        ->from(route('admin.sarpras.index', $team))
        ->post(route('admin.sarpras.store', $team), [
            'nama' => 'Papan Tulis',
            'kategori' => SarprasKategori::RuangKelas->value,
            'jumlah' => 2,
            'kondisi' => SarprasKondisi::Baik->value,
            'foto' => UploadedFile::fake()->image('papan-tulis.jpg'),
        ])
        ->assertRedirect(route('admin.sarpras.index', $team));

    $item = SarprasItem::firstOrFail();

    Storage::disk('public')->assertExists($item->foto_path);

    $this
        ->actingAs($user)
        ->get(route('admin.sarpras.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('items.0.foto_path', $item->foto_path)
            ->where('items.0.foto_url', Storage::disk('public')->url($item->foto_path)));
});

test('an admin lembaga can replace a foto when updating a sarpras item', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::AdminLembaga->value]);
    $item = SarprasItem::factory()->create([
        'team_id' => $team->id,
        'foto_path' => 'sarpras/lama.jpg',
    ]);

    Storage::disk('public')->put('sarpras/lama.jpg', 'lama');

    $this
        ->actingAs($user)
        ->from(route('admin.sarpras.index', $team))
        ->put(route('admin.sarpras.update', [$team, $item]), [
            'nama' => $item->nama,
            'kategori' => $item->kategori->value,
            'jumlah' => $item->jumlah,
            'kondisi' => $item->kondisi->value,
            'foto' => UploadedFile::fake()->image('baru.jpg'),
        ])
        ->assertRedirect(route('admin.sarpras.index', $team));

    $item->refresh();

    Storage::disk('public')->assertMissing('sarpras/lama.jpg');
    Storage::disk('public')->assertExists($item->foto_path);
});

test('an admin lembaga can remove a foto when updating a sarpras item', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::AdminLembaga->value]);
    $item = SarprasItem::factory()->create([
        'team_id' => $team->id,
        'foto_path' => 'sarpras/lama.jpg',
    ]);

    Storage::disk('public')->put('sarpras/lama.jpg', 'lama');

    $this
        ->actingAs($user)
        ->from(route('admin.sarpras.index', $team))
        ->put(route('admin.sarpras.update', [$team, $item]), [
            'nama' => $item->nama,
            'kategori' => $item->kategori->value,
            'jumlah' => $item->jumlah,
            'kondisi' => $item->kondisi->value,
            'remove_foto' => true,
        ])
        ->assertRedirect(route('admin.sarpras.index', $team));

    Storage::disk('public')->assertMissing('sarpras/lama.jpg');
    expect($item->fresh()->foto_path)->toBeNull();
});

test('deleting a sarpras item removes its foto file', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::AdminLembaga->value]);
    $item = SarprasItem::factory()->create([
        'team_id' => $team->id,
        'foto_path' => 'sarpras/lama.jpg',
    ]);

    Storage::disk('public')->put('sarpras/lama.jpg', 'lama');

    $this
        ->actingAs($user)
        ->from(route('admin.sarpras.index', $team))
        ->delete(route('admin.sarpras.destroy', [$team, $item]))
        ->assertRedirect(route('admin.sarpras.index', $team));

    Storage::disk('public')->assertMissing('sarpras/lama.jpg');
});

test('a sarpras item foto must be a valid image', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::AdminLembaga->value]);

    $this
        ->actingAs($user)
        ->from(route('admin.sarpras.index', $team))
        ->post(route('admin.sarpras.store', $team), [
            'nama' => 'Papan Tulis',
            'kategori' => SarprasKategori::RuangKelas->value,
            'jumlah' => 2,
            'kondisi' => SarprasKondisi::Baik->value,
            'foto' => UploadedFile::fake()->create('bukan-gambar.txt', 100),
        ])
        ->assertSessionHasErrors('foto');

    expect(SarprasItem::count())->toBe(0);
});

test('an admin lembaga can report a perbaikan request', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::AdminLembaga->value]);
    $item = SarprasItem::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->from(route('admin.sarpras.index', $team))
        ->post(route('admin.sarpras.perbaikan.store', $team), [
            'sarpras_item_id' => $item->id,
            'deskripsi' => 'Lampu meja rusak',
        ])
        ->assertRedirect(route('admin.sarpras.index', $team));

    $this->assertDatabaseHas('perbaikan_sarpras', [
        'team_id' => $team->id,
        'sarpras_item_id' => $item->id,
        'pelapor_user_id' => $user->id,
        'deskripsi' => 'Lampu meja rusak',
        'status' => PerbaikanStatus::Pending->value,
    ]);
});

test('a perbaikan request cannot reference an item from another team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::AdminLembaga->value]);
    $item = SarprasItem::factory()->create(['team_id' => $otherTeam->id]);

    $this
        ->actingAs($user)
        ->post(route('admin.sarpras.perbaikan.store', $team), [
            'sarpras_item_id' => $item->id,
            'deskripsi' => 'Bukan item kami',
        ])
        ->assertSessionHasErrors('sarpras_item_id');

    $this->assertDatabaseCount('perbaikan_sarpras', 0);
});

test('an admin lembaga can update the status of a perbaikan request', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::AdminLembaga->value]);
    $perbaikan = PerbaikanSarpras::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->from(route('admin.sarpras.index', $team))
        ->patch(route('admin.sarpras.perbaikan.update', [$team, $perbaikan]), [
            'status' => PerbaikanStatus::Diproses->value,
            'catatan' => 'Menunggu sparepart',
        ])
        ->assertRedirect(route('admin.sarpras.index', $team));

    $this->assertDatabaseHas('perbaikan_sarpras', [
        'id' => $perbaikan->id,
        'status' => PerbaikanStatus::Diproses->value,
        'catatan' => 'Menunggu sparepart',
    ]);
});

test('an admin lembaga can delete a perbaikan request', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::AdminLembaga->value]);
    $perbaikan = PerbaikanSarpras::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->from(route('admin.sarpras.index', $team))
        ->delete(route('admin.sarpras.perbaikan.destroy', [$team, $perbaikan]))
        ->assertRedirect(route('admin.sarpras.index', $team));

    $this->assertDatabaseMissing('perbaikan_sarpras', ['id' => $perbaikan->id]);
});
