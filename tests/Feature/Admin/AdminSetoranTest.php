<?php

use App\Enums\HafalanType;
use App\Enums\TeamRole;
use App\Models\Hafalan;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\Team;
use App\Models\User;
use App\Notifications\HafalanSetoranNotification;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

test('unauthenticated users are redirected to login from the setoran page', function () {
    $team = Team::factory()->create();

    $this->get(route('admin.setoran.index', $team))->assertRedirectToRoute('login');
});

test('a team owner can view the setoran page', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    Hafalan::factory()->hafalanBaru()->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'ustad_user_id' => $user->id,
    ]);

    $this
        ->actingAs($user)
        ->get(route('admin.setoran.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/setoran/index')
            ->has('santris', 1)
            ->has('recent', 1)
            ->where('recent.0.santri.name', $santri->name)
            ->where('filters.search', ''));
});

test('an ustaz can view the setoran page', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Ustaz->value]);

    $this
        ->actingAs($user)
        ->get(route('admin.setoran.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('admin/setoran/index'));
});

test('a member cannot view the setoran page', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $this
        ->actingAs($user)
        ->get(route('admin.setoran.index', $team))
        ->assertForbidden();
});

test('an ustaz can record a setoran for a santri', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Ustaz->value]);
    $kelas = Kelas::factory()->create([
        'team_id' => $team->id,
        'ustad_user_id' => $user->id,
    ]);
    $santri = Santri::factory()->create([
        'team_id' => $team->id,
        'kelas_id' => $kelas->id,
    ]);

    $this
        ->actingAs($user)
        ->from(route('admin.setoran.index', $team))
        ->post(route('admin.setoran.store', $team), [
            'santri_id' => $santri->id,
            'type' => HafalanType::HafalanBaru->value,
            'juz' => 2,
            'surat' => 'Al-Baqarah',
            'ayat_from' => 1,
            'ayat_to' => 5,
            'tanggal' => '2026-08-10',
        ])
        ->assertRedirect(route('admin.setoran.index', $team));

    $hafalan = Hafalan::firstOrFail();

    expect($hafalan->santri_id)->toBe($santri->id)
        ->and($hafalan->team_id)->toBe($team->id)
        ->and($hafalan->ustad_user_id)->toBe($user->id)
        ->and($hafalan->type)->toBe(HafalanType::HafalanBaru)
        ->and($hafalan->surat)->toBe('Al-Baqarah');
});

test('a setoran cannot be recorded for a santri outside the scope', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create(['team_id' => $otherTeam->id]);

    $this
        ->actingAs($user)
        ->from(route('admin.setoran.index', $team))
        ->post(route('admin.setoran.store', $team), [
            'santri_id' => $santri->id,
            'type' => HafalanType::HafalanBaru->value,
            'surat' => 'Al-Ikhlas',
            'tanggal' => '2026-08-10',
        ])
        ->assertNotFound();

    expect(Hafalan::count())->toBe(0);
});

test('the santri is required when recording a setoran', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this
        ->actingAs($user)
        ->from(route('admin.setoran.index', $team))
        ->post(route('admin.setoran.store', $team), [
            'type' => HafalanType::HafalanBaru->value,
            'surat' => 'Al-Ikhlas',
            'tanggal' => '2026-08-10',
        ])
        ->assertSessionHasErrors('santri_id');

    expect(Hafalan::count())->toBe(0);
});

test('an ustaz only sees santri from their assigned kelas', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Ustaz->value]);
    $kelas = Kelas::factory()->create([
        'team_id' => $team->id,
        'ustad_user_id' => $user->id,
    ]);
    $otherKelas = Kelas::factory()->create(['team_id' => $team->id]);

    $inKelas = Santri::factory()->create([
        'team_id' => $team->id,
        'kelas_id' => $kelas->id,
    ]);
    $outside = Santri::factory()->create([
        'team_id' => $team->id,
        'kelas_id' => $otherKelas->id,
    ]);

    $this
        ->actingAs($user)
        ->get(route('admin.setoran.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('santris', 1)
            ->where('santris.0.id', $inKelas->id)
            ->where('santris.0.kelas.id', $kelas->id));
});

test('an ustaz cannot record a setoran for a santri outside their kelas', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Ustaz->value]);
    $kelas = Kelas::factory()->create([
        'team_id' => $team->id,
        'ustad_user_id' => $user->id,
    ]);
    $outside = Santri::factory()->create([
        'team_id' => $team->id,
        'kelas_id' => Kelas::factory()->create(['team_id' => $team->id])->id,
    ]);

    $this
        ->actingAs($user)
        ->from(route('admin.setoran.index', $team))
        ->post(route('admin.setoran.store', $team), [
            'santri_id' => $outside->id,
            'type' => HafalanType::HafalanBaru->value,
            'surat' => 'Al-Ikhlas',
            'tanggal' => '2026-08-10',
        ])
        ->assertNotFound();

    expect(Hafalan::count())->toBe(0);
});

test('an ustaz without an assigned kelas cannot record a setoran', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Ustaz->value]);
    $santri = Santri::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->get(route('admin.setoran.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->has('santris', 0));

    $this
        ->actingAs($user)
        ->from(route('admin.setoran.index', $team))
        ->post(route('admin.setoran.store', $team), [
            'santri_id' => $santri->id,
            'type' => HafalanType::HafalanBaru->value,
            'surat' => 'Al-Ikhlas',
            'tanggal' => '2026-08-10',
        ])
        ->assertNotFound();

    expect(Hafalan::count())->toBe(0);
});

test('a notification is sent to the wali when a setoran is recorded', function () {
    Notification::fake();

    $user = User::factory()->create();
    $wali = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create([
        'team_id' => $team->id,
        'wali_user_id' => $wali->id,
    ]);

    $this
        ->actingAs($user)
        ->from(route('admin.setoran.index', $team))
        ->post(route('admin.setoran.store', $team), [
            'santri_id' => $santri->id,
            'type' => HafalanType::HafalanBaru->value,
            'surat' => 'Al-Ikhlas',
            'tanggal' => '2026-08-10',
        ])
        ->assertRedirect(route('admin.setoran.index', $team));

    Notification::assertSentTo(
        $wali,
        HafalanSetoranNotification::class,
        fn (HafalanSetoranNotification $notification) => $notification->hafalan->santri_id === $santri->id,
    );
});

test('no notification is sent when the santri has no wali account', function () {
    Notification::fake();

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $santri = Santri::factory()->create([
        'team_id' => $team->id,
        'wali_user_id' => null,
    ]);

    $this
        ->actingAs($user)
        ->from(route('admin.setoran.index', $team))
        ->post(route('admin.setoran.store', $team), [
            'santri_id' => $santri->id,
            'type' => HafalanType::HafalanBaru->value,
            'surat' => 'Al-Ikhlas',
            'tanggal' => '2026-08-10',
        ])
        ->assertRedirect(route('admin.setoran.index', $team));

    Notification::assertNothingSent();
});
