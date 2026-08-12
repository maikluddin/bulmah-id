<?php

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Team;
use App\Models\User;

test('a post can be created', function () {
    $post = Post::factory()->create();

    expect($post)->toBeInstanceOf(Post::class);
    $this->assertDatabaseHas('posts', ['id' => $post->id]);
});

test('a post defaults to draft status', function () {
    $post = Post::factory()->draft()->create();

    expect($post->status)->toBe(PostStatus::Draft);
    expect($post->published_at)->toBeNull();
});

test('a post slug is auto generated from the title', function () {
    $post = Post::factory()->create(['title' => 'Berita Penting Hari Ini']);

    expect($post->slug)->toBe('berita-penting-hari-ini');
});

test('a post belongs to a lembaga (team)', function () {
    $team = Team::factory()->create();
    $post = Post::factory()->create(['team_id' => $team->id]);

    expect($post->team->is($team))->toBeTrue();
});

test('a post belongs to a category', function () {
    $category = PostCategory::factory()->create();
    $post = Post::factory()->create(['category_id' => $category->id]);

    expect($post->category->is($category))->toBeTrue();
});

test('a post belongs to an author', function () {
    $author = User::factory()->create();
    $post = Post::factory()->create(['author_id' => $author->id]);

    expect($post->author->is($author))->toBeTrue();
});

test('published scope only returns published posts', function () {
    Post::factory()->count(2)->create();
    Post::factory()->draft()->create();

    expect(Post::published()->count())->toBe(2);
});

test('a post can be soft deleted', function () {
    $post = Post::factory()->create();

    $post->delete();

    $this->assertSoftDeleted('posts', ['id' => $post->id]);
    expect(Post::find($post->id))->toBeNull();
});

test('a category has many posts', function () {
    $category = PostCategory::factory()->create();
    $posts = Post::factory()->count(3)->create(['category_id' => $category->id]);

    expect($category->posts->pluck('id'))->toEqualCanonicalizing($posts->pluck('id'));
});
