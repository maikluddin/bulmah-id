<?php

use App\Enums\PostStatus;
use App\Enums\TeamRole;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('unauthenticated users are redirected to login', function () {
    $team = Team::factory()->create();

    $this->get(route('admin.posts.index', $team))->assertRedirectToRoute('login');
});

test('users cannot view the posts of a team they do not belong to', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    $this
        ->actingAs($user)
        ->get(route('admin.posts.index', $team))
        ->assertForbidden();
});

test('a team owner can view the posts index', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Post::factory()->count(3)->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->get(route('admin.posts.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/posts/index')
            ->has('posts.data', 3)
            ->where('team.slug', $team->slug));
});

test('a member without the posts permission is forbidden', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $this
        ->actingAs($user)
        ->get(route('admin.posts.index', $team))
        ->assertForbidden();
});

test('an ustaz can create a post', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Ustaz->value]);
    $category = PostCategory::factory()->create(['team_id' => $team->id]);

    $response = $this
        ->actingAs($user)
        ->post(route('admin.posts.store', $team), [
            'title' => 'Berita Baru',
            'category_id' => $category->id,
            'excerpt' => 'Ringkasan',
            'body' => 'Isi berita lengkap.',
            'status' => PostStatus::Draft->value,
            'published_at' => null,
        ]);

    $post = Post::firstOrFail();

    $response->assertRedirect(route('admin.posts.edit', [$team, $post]));

    $this->assertDatabaseHas('posts', [
        'team_id' => $team->id,
        'author_id' => $user->id,
        'title' => 'Berita Baru',
        'status' => PostStatus::Draft->value,
        'published_at' => null,
    ]);
});

test('a published post gets a default published_at when empty', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this
        ->actingAs($user)
        ->post(route('admin.posts.store', $team), [
            'title' => 'Berita Terbit',
            'body' => 'Isi berita.',
            'status' => PostStatus::Published->value,
            'published_at' => null,
        ])
        ->assertRedirect();

    expect(Post::first()->published_at->isToday())->toBeTrue();
});

test('a draft post cannot be created without a body', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this
        ->actingAs($user)
        ->post(route('admin.posts.store', $team), [
            'title' => 'Tanpa Isi',
            'status' => PostStatus::Draft->value,
        ])
        ->assertSessionHasErrors('body');

    expect(Post::count())->toBe(0);
});

test('a team owner can update a post', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $post = Post::factory()->draft()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->put(route('admin.posts.update', [$team, $post]), [
            'title' => 'Judul Diperbarui',
            'body' => 'Konten baru.',
            'status' => PostStatus::Published->value,
            'published_at' => '2026-01-15',
        ])
        ->assertRedirect(route('admin.posts.edit', [$team, $post]));

    $post->refresh();

    expect($post->title)->toBe('Judul Diperbarui')
        ->and($post->status)->toBe(PostStatus::Published)
        ->and($post->published_at->format('Y-m-d'))->toBe('2026-01-15');
});

test('a post belonging to another team cannot be edited', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $post = Post::factory()->create(['team_id' => $otherTeam->id]);

    $this
        ->actingAs($user)
        ->get(route('admin.posts.edit', [$team, $post]))
        ->assertNotFound();
});

test('a team owner can delete a post', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $post = Post::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->delete(route('admin.posts.destroy', [$team, $post]))
        ->assertRedirect(route('admin.posts.index', $team))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Berita berhasil dihapus.']);

    $this->assertSoftDeleted('posts', ['id' => $post->id]);
});

test('the posts index is scoped to the current team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Post::factory()->count(2)->create(['team_id' => $team->id]);
    Post::factory()->count(3)->create(['team_id' => $otherTeam->id]);

    $this
        ->actingAs($user)
        ->get(route('admin.posts.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->has('posts.data', 2));
});

test('the posts index aggregates posts across owned lembaga for an owner', function () {
    $user = User::factory()->create();
    $teamA = Team::factory()->create();
    $teamB = Team::factory()->create();

    $user->teams()->attach($teamA, ['role' => TeamRole::Owner->value]);
    $user->teams()->attach($teamB, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($teamA);

    Post::factory()->count(2)->create(['team_id' => $teamA->id]);
    Post::factory()->count(3)->create(['team_id' => $teamB->id]);

    $this
        ->actingAs($user)
        ->get(route('admin.posts.index', $teamA))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 5)
            ->where('isAggregate', true)
            ->where('posts.data.0.team.slug', $teamB->slug));
});

test('a member without the posts permission cannot create a post', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $this
        ->actingAs($user)
        ->post(route('admin.posts.store', $team), [
            'title' => 'Tidak Boleh',
            'body' => 'Isi.',
            'status' => PostStatus::Draft->value,
        ])
        ->assertForbidden();

    expect(Post::count())->toBe(0);
});

test('a member without the posts permission cannot update a post', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);
    $post = Post::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->put(route('admin.posts.update', [$team, $post]), [
            'title' => 'Diubah',
            'body' => 'Isi.',
            'status' => PostStatus::Draft->value,
        ])
        ->assertForbidden();
});

test('a member without the posts permission cannot delete a post', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);
    $post = Post::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->delete(route('admin.posts.destroy', [$team, $post]))
        ->assertForbidden();

    $this->assertNotSoftDeleted('posts', ['id' => $post->id]);
});

test('a post belonging to another team cannot be updated', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $post = Post::factory()->create(['team_id' => $otherTeam->id]);

    $this
        ->actingAs($user)
        ->put(route('admin.posts.update', [$team, $post]), [
            'title' => 'Diubah',
            'body' => 'Isi.',
            'status' => PostStatus::Draft->value,
        ])
        ->assertNotFound();
});

test('a post belonging to another team cannot be deleted', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $post = Post::factory()->create(['team_id' => $otherTeam->id]);

    $this
        ->actingAs($user)
        ->delete(route('admin.posts.destroy', [$team, $post]))
        ->assertNotFound();

    $this->assertNotSoftDeleted('posts', ['id' => $post->id]);
});

test('a post cannot reference a category from another team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $foreignCategory = PostCategory::factory()->create(['team_id' => $otherTeam->id]);

    $this
        ->actingAs($user)
        ->post(route('admin.posts.store', $team), [
            'title' => 'Berita',
            'category_id' => $foreignCategory->id,
            'body' => 'Isi.',
            'status' => PostStatus::Draft->value,
        ])
        ->assertSessionHasErrors('category_id');

    expect(Post::count())->toBe(0);
});

test('posts with the same title get unique slugs within a team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this
        ->actingAs($user)
        ->post(route('admin.posts.store', $team), [
            'title' => 'Berita Sama',
            'body' => 'Isi pertama.',
            'status' => PostStatus::Draft->value,
        ])
        ->assertRedirect();

    $this
        ->actingAs($user)
        ->post(route('admin.posts.store', $team), [
            'title' => 'Berita Sama',
            'body' => 'Isi kedua.',
            'status' => PostStatus::Draft->value,
        ])
        ->assertRedirect();

    expect(Post::where('team_id', $team->id)->pluck('slug')->all())
        ->toBe(['berita-sama', 'berita-sama-1']);
});

test('the posts index can be filtered by status', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    Post::factory()->draft()->count(2)->create(['team_id' => $team->id]);
    Post::factory()->count(3)->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->get(route('admin.posts.index', [$team, 'status' => PostStatus::Published->value]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 3)
            ->where('filters.status', PostStatus::Published->value));
});

test('the posts index can be filtered by category', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $category = PostCategory::factory()->create(['team_id' => $team->id]);

    Post::factory()->count(2)->create(['team_id' => $team->id, 'category_id' => $category->id]);
    Post::factory()->count(4)->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->get(route('admin.posts.index', [$team, 'category' => $category->id]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->has('posts.data', 2));
});

test('a team owner can upload a cover when creating a post', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $response = $this
        ->actingAs($user)
        ->post(route('admin.posts.store', $team), [
            'title' => 'Berita Bersampul',
            'body' => 'Isi berita.',
            'status' => PostStatus::Draft->value,
            'cover' => UploadedFile::fake()->image('sampul.jpg'),
        ]);

    $post = Post::firstOrFail();

    $response->assertRedirect(route('admin.posts.edit', [$team, $post]));

    Storage::disk('public')->assertExists($post->cover_path);

    $this
        ->actingAs($user)
        ->get(route('admin.posts.edit', [$team, $post]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('post.cover_path', $post->cover_path)
            ->where('post.cover_url', Storage::disk('public')->url($post->cover_path)));
});

test('a team owner can replace a cover when updating a post', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $post = Post::factory()->create([
        'team_id' => $team->id,
        'cover_path' => 'posts/lama.jpg',
    ]);

    Storage::disk('public')->put('posts/lama.jpg', 'lama');

    $this
        ->actingAs($user)
        ->put(route('admin.posts.update', [$team, $post]), [
            'title' => $post->title,
            'body' => $post->body,
            'status' => $post->status->value,
            'cover' => UploadedFile::fake()->image('baru.jpg'),
        ])
        ->assertRedirect(route('admin.posts.edit', [$team, $post]));

    $post->refresh();

    Storage::disk('public')->assertMissing('posts/lama.jpg');
    Storage::disk('public')->assertExists($post->cover_path);
});

test('a team owner can remove a cover when updating a post', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $post = Post::factory()->create([
        'team_id' => $team->id,
        'cover_path' => 'posts/lama.jpg',
    ]);

    Storage::disk('public')->put('posts/lama.jpg', 'lama');

    $this
        ->actingAs($user)
        ->put(route('admin.posts.update', [$team, $post]), [
            'title' => $post->title,
            'body' => $post->body,
            'status' => $post->status->value,
            'remove_cover' => true,
        ])
        ->assertRedirect(route('admin.posts.edit', [$team, $post]));

    Storage::disk('public')->assertMissing('posts/lama.jpg');
    expect($post->fresh()->cover_path)->toBeNull();
});

test('deleting a post removes its cover file', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $post = Post::factory()->create([
        'team_id' => $team->id,
        'cover_path' => 'posts/lama.jpg',
    ]);

    Storage::disk('public')->put('posts/lama.jpg', 'lama');

    $this
        ->actingAs($user)
        ->delete(route('admin.posts.destroy', [$team, $post]))
        ->assertRedirect(route('admin.posts.index', $team));

    Storage::disk('public')->assertMissing('posts/lama.jpg');
});

test('a cover must be a valid image', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this
        ->actingAs($user)
        ->post(route('admin.posts.store', $team), [
            'title' => 'Berita Tanpa Sampul',
            'body' => 'Isi.',
            'status' => PostStatus::Draft->value,
            'cover' => UploadedFile::fake()->create('bukan-gambar.txt', 100),
        ])
        ->assertSessionHasErrors('cover');

    expect(Post::count())->toBe(0);
});
