<?php

use App\Enums\TeamRole;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('a team owner can view the categories index', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    PostCategory::factory()->count(2)->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->get(route('admin.categories.index', $team))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/categories/index')
            ->has('categories', 2));
});

test('a member without the categories permission is forbidden', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $this
        ->actingAs($user)
        ->get(route('admin.categories.index', $team))
        ->assertForbidden();
});

test('the categories index aggregates categories across owned lembaga for an owner', function () {
    $user = User::factory()->create();
    $teamA = Team::factory()->create();
    $teamB = Team::factory()->create();

    $user->teams()->attach($teamA, ['role' => TeamRole::Owner->value]);
    $user->teams()->attach($teamB, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($teamA);

    PostCategory::factory()->count(2)->create(['team_id' => $teamA->id]);
    PostCategory::factory()->count(3)->create(['team_id' => $teamB->id]);

    $this
        ->actingAs($user)
        ->get(route('admin.categories.index', $teamA))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('categories', 5)
            ->where('isAggregate', true)
            ->has('categories.0.team.slug'));
});

test('a team owner can create a category', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this
        ->actingAs($user)
        ->post(route('admin.categories.store', $team), [
            'name' => 'Kegiatan',
            'description' => 'Kategori kegiatan lembaga.',
        ])
        ->assertRedirect()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Kategori berhasil dibuat.']);

    $this->assertDatabaseHas('post_categories', [
        'team_id' => $team->id,
        'name' => 'Kegiatan',
    ]);
});

test('a category name is required', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this
        ->actingAs($user)
        ->post(route('admin.categories.store', $team), ['name' => ''])
        ->assertSessionHasErrors('name');

    expect(PostCategory::count())->toBe(0);
});

test('a team owner can update a category', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $category = PostCategory::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->put(route('admin.categories.update', [$team, $category]), [
            'name' => 'Kegiatan Rutin',
            'description' => null,
        ])
        ->assertRedirect();

    expect($category->fresh()->name)->toBe('Kegiatan Rutin');
});

test('a category belonging to another team cannot be updated', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $category = PostCategory::factory()->create(['team_id' => $otherTeam->id]);

    $this
        ->actingAs($user)
        ->put(route('admin.categories.update', [$team, $category]), [
            'name' => 'Tidak Sah',
        ])
        ->assertNotFound();
});

test('a team owner can delete a category', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $category = PostCategory::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->delete(route('admin.categories.destroy', [$team, $category]))
        ->assertRedirect()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Kategori berhasil dihapus.']);

    expect(PostCategory::find($category->id))->toBeNull();
});

test('a category that still has posts cannot be deleted', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $category = PostCategory::factory()->create(['team_id' => $team->id]);
    Post::factory()->create(['team_id' => $team->id, 'category_id' => $category->id]);

    $this
        ->actingAs($user)
        ->delete(route('admin.categories.destroy', [$team, $category]))
        ->assertSessionHasErrors('category');

    expect(PostCategory::find($category->id))->not->toBeNull();
});

test('a category belonging to another team cannot be deleted', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $category = PostCategory::factory()->create(['team_id' => $otherTeam->id]);

    $this
        ->actingAs($user)
        ->delete(route('admin.categories.destroy', [$team, $category]))
        ->assertNotFound();

    expect(PostCategory::find($category->id))->not->toBeNull();
});

test('a member without the categories permission cannot delete a category', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);
    $category = PostCategory::factory()->create(['team_id' => $team->id]);

    $this
        ->actingAs($user)
        ->delete(route('admin.categories.destroy', [$team, $category]))
        ->assertForbidden();

    expect(PostCategory::find($category->id))->not->toBeNull();
});

test('categories with the same name get unique slugs within a team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this
        ->actingAs($user)
        ->post(route('admin.categories.store', $team), ['name' => 'Umum'])
        ->assertRedirect();

    $this
        ->actingAs($user)
        ->post(route('admin.categories.store', $team), ['name' => 'Umum'])
        ->assertRedirect();

    expect(PostCategory::where('team_id', $team->id)->pluck('slug')->all())
        ->toBe(['umum', 'umum-1']);
});
