<?php

use App\Enums\SantriGender;
use App\Enums\TeamRole;
use App\Models\Hafalan;
use App\Models\HafalanKategori;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Santri;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
});

test('dashboard includes pending invitations for the authenticated user', function () {
    $owner = User::factory()->create(['name' => 'Taylor Otwell']);
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create(['name' => 'Laravel Team']);

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 1)
        ->where('pendingInvitations.0.code', $invitation->code)
        ->where('pendingInvitations.0.inviterName', 'Taylor Otwell')
        ->where('pendingInvitations.0.team.name', 'Laravel Team')
        ->where('pendingInvitations.0.team.slug', $team->slug)
        ->missing('pendingInvitations.0.teamName'),
    );
});

test('dashboard does not include accepted invitations', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    TeamInvitation::factory()->accepted()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 0),
    );
});

test('dashboard excludes expired invitations without deleting them', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 0),
    );

    $this->assertDatabaseHas('team_invitations', [
        'id' => $invitation->id,
    ]);
});

test('dashboard does not include or delete other users invitations', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'someone@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 0),
    );

    $this->assertDatabaseHas('team_invitations', [
        'id' => $invitation->id,
    ]);
});

test('dashboard includes team-scoped stats', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $category = PostCategory::factory()->create(['team_id' => $team->id]);
    Post::factory()->count(3)->create([
        'team_id' => $team->id,
        'category_id' => $category->id,
    ]);
    Post::factory()->draft()->count(2)->create([
        'team_id' => $team->id,
        'category_id' => $category->id,
    ]);

    Santri::factory()->count(2)->create(['team_id' => $team->id]);
    Santri::factory()->alumni()->count(1)->create(['team_id' => $team->id]);

    $otherTeam = Team::factory()->create();
    Post::factory()->count(4)->create(['team_id' => $otherTeam->id]);
    Santri::factory()->count(3)->create(['team_id' => $otherTeam->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', ['current_team' => $team->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('stats.posts', 5)
        ->where('stats.published_posts', 3)
        ->where('stats.categories', 1)
        ->where('stats.santri', 3)
        ->where('stats.active_santri', 2)
        ->where('team.id', $team->id)
        ->where('team.name', $team->name)
        ->where('team.slug', $team->slug),
    );
});

test('dashboard includes recent posts and santri for the current team', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $category = PostCategory::factory()->create(['team_id' => $team->id]);
    $post = Post::factory()->create([
        'team_id' => $team->id,
        'category_id' => $category->id,
        'title' => 'Berita Terbaru',
    ]);

    $santri = Santri::factory()->create([
        'team_id' => $team->id,
        'name' => 'Ahmad Fauzi',
        'nis' => '20240001',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', ['current_team' => $team->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('recentPosts', 1)
        ->where('recentPosts.0.id', $post->id)
        ->where('recentPosts.0.title', 'Berita Terbaru')
        ->where('recentPosts.0.status', 'published')
        ->where('recentPosts.0.status_label', 'Terbit')
        ->where('recentPosts.0.category.id', $category->id)
        ->where('recentPosts.0.category.name', $category->name)
        ->has('recentSantri', 1)
        ->where('recentSantri.0.id', $santri->id)
        ->where('recentSantri.0.name', 'Ahmad Fauzi')
        ->where('recentSantri.0.nis', '20240001')
        ->where('recentSantri.0.status', 'aktif')
        ->where('recentSantri.0.status_label', 'Aktif'),
    );
});

test('dashboard is not aggregate for a user who owns no lembaga', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', ['current_team' => $team->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('isAggregate', false));
});

test('dashboard aggregates stats and recent data across owned lembaga for an owner', function () {
    $user = User::factory()->create();
    $teamA = Team::factory()->create();
    $teamB = Team::factory()->create();

    $user->teams()->attach($teamA, ['role' => TeamRole::Owner->value]);
    $user->teams()->attach($teamB, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($teamA);

    Post::factory()->count(2)->create(['team_id' => $teamA->id]);
    Post::factory()->count(3)->create(['team_id' => $teamB->id]);
    Santri::factory()->count(1)->create(['team_id' => $teamA->id]);
    Santri::factory()->count(4)->create(['team_id' => $teamB->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', ['current_team' => $teamA->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('stats.posts', 5)
        ->where('stats.published_posts', 5)
        ->where('stats.santri', 5)
        ->where('stats.active_santri', 5)
        ->where('isAggregate', true)
        ->has('recentPosts', 5)
        ->where('recentPosts.0.team.slug', $teamB->slug)
        ->has('recentSantri', 5)
        ->where('recentSantri.0.team.slug', $teamB->slug),
    );
});

test('dashboard scopes santri stats by gender for a scoped admin', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, [
        'role' => TeamRole::AdminLembaga->value,
        'santri_gender' => SantriGender::LakiLaki,
    ]);
    $user->switchTeam($team);

    Santri::factory()->count(2)->create([
        'team_id' => $team->id,
        'gender' => SantriGender::LakiLaki,
    ]);
    Santri::factory()->count(3)->create([
        'team_id' => $team->id,
        'gender' => SantriGender::Perempuan,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', ['current_team' => $team->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('stats.santri', 2)
        ->where('stats.active_santri', 2)
        ->where('stats.posts', 0)
        ->where('isAggregate', false),
    );
});

test('dashboard includes team-scoped setoran stats and recent setorans', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $santri = Santri::factory()->create(['team_id' => $team->id]);
    Hafalan::factory()->count(3)->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'tanggal' => now()->format('Y-m-d'),
    ]);
    Hafalan::factory()->count(2)->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'tanggal' => now()->subMonths(2)->format('Y-m-d'),
    ]);

    $otherTeam = Team::factory()->create();
    $otherSantri = Santri::factory()->create(['team_id' => $otherTeam->id]);
    Hafalan::factory()->count(4)->create([
        'team_id' => $otherTeam->id,
        'santri_id' => $otherSantri->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', ['current_team' => $team->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('stats.setoran', 5)
        ->where('stats.setoran_bulan_ini', 3)
        ->has('recentSetorans', 5));
});

test('dashboard recent setorans include the santri and kategori', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $santri = Santri::factory()->create(['team_id' => $team->id, 'name' => 'Ahmad Fauzi']);
    $kategori = HafalanKategori::factory()->create(['team_id' => $team->id, 'nama' => 'Juz Amma']);
    $hafalan = Hafalan::factory()->create([
        'team_id' => $team->id,
        'santri_id' => $santri->id,
        'kategori_id' => $kategori->id,
        'surat' => 'Ar-Rahman',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', ['current_team' => $team->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('recentSetorans.0.id', $hafalan->id)
        ->where('recentSetorans.0.santri.name', 'Ahmad Fauzi')
        ->where('recentSetorans.0.surat', 'Ar-Rahman')
        ->where('recentSetorans.0.kategori.nama', 'Juz Amma'));
});

test('dashboard scopes setoran stats by gender for a scoped admin', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, [
        'role' => TeamRole::AdminLembaga->value,
        'santri_gender' => SantriGender::LakiLaki,
    ]);
    $user->switchTeam($team);

    $male = Santri::factory()->create(['team_id' => $team->id, 'gender' => SantriGender::LakiLaki]);
    $female = Santri::factory()->create(['team_id' => $team->id, 'gender' => SantriGender::Perempuan]);

    Hafalan::factory()->count(2)->create([
        'team_id' => $team->id,
        'santri_id' => $male->id,
        'tanggal' => now()->format('Y-m-d'),
    ]);
    Hafalan::factory()->count(3)->create([
        'team_id' => $team->id,
        'santri_id' => $female->id,
        'tanggal' => now()->format('Y-m-d'),
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', ['current_team' => $team->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('stats.setoran', 2)
        ->where('stats.setoran_bulan_ini', 2));
});
