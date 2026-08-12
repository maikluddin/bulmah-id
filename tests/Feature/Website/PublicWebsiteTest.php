<?php

use App\Enums\LembagaType;
use App\Enums\SantriGender;
use App\Enums\SantriStatus;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Team;
use App\Models\Yayasan;
use Inertia\Testing\AssertableInertia as Assert;

test('the home page renders public data', function () {
    $yayasan = Yayasan::factory()->create(['name' => 'Yayasan Bulmah']);
    $lembaga = Team::factory()->create([
        'name' => 'Pondok Al-Bulmah',
        'yayasan_id' => $yayasan->id,
    ]);
    Team::factory()->personal()->create();
    $post = Post::factory()->create(['team_id' => $lembaga->id]);
    Post::factory()->draft()->create(['team_id' => $lembaga->id]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/home')
            ->where('yayasan.id', $yayasan->id)
            ->where('stats.lembaga', 3)
            ->where('stats.santri', 0)
            ->where('stats.post', 1)
            ->has('lembagas', 3)
            ->has('posts', 1)
            ->where('posts.0.title', $post->title)
        );
});

test('the home page shows santri stats only for aktif santri', function () {
    $lembaga = Team::factory()->create();
    $lembaga->santris()->create([
        'nis' => 'S001',
        'name' => 'Ahmad',
        'gender' => SantriGender::LakiLaki,
        'status' => SantriStatus::Aktif,
    ]);
    $lembaga->santris()->create([
        'nis' => 'S002',
        'name' => 'Budi',
        'gender' => SantriGender::LakiLaki,
        'status' => SantriStatus::Alumni,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.santri', 1)
        );
});

test('guest visitors see the public home page without auth errors', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('public/home'));
});

test('the news index page lists published posts only', function () {
    $published = Post::factory()->count(3)->create();
    Post::factory()->draft()->create();

    $this->get('/berita')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/news')
            ->has('posts.data', 3)
            ->where('posts.total', 3)
        );
});

test('the news index exposes filter options for non personal teams', function () {
    $lembaga = Team::factory()->create(['name' => 'Pondok Al-Bulmah']);
    $category = PostCategory::factory()->create(['team_id' => $lembaga->id]);
    Team::factory()->personal()->create();

    $this->get('/berita')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('lembagas', 1)
            ->where('lembagas.0.slug', $lembaga->slug)
            ->has('categories', 1)
            ->where('categories.0.team_slug', $lembaga->slug)
            ->where('categories.0.slug', $category->slug)
        );
});

test('the news index filters posts by lembaga', function () {
    $lembagaA = Team::factory()->create(['name' => 'Pondok A']);
    $lembagaB = Team::factory()->create(['name' => 'Pondok B']);
    $postA = Post::factory()->create(['team_id' => $lembagaA->id]);
    Post::factory()->create(['team_id' => $lembagaB->id]);

    $this->get("/berita?lembaga={$lembagaA->slug}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 1)
            ->where('posts.data.0.id', $postA->id)
            ->where('filters.lembaga', $lembagaA->slug)
        );
});

test('the news index filters posts by category', function () {
    $lembaga = Team::factory()->create();
    $pengumuman = PostCategory::factory()->create([
        'team_id' => $lembaga->id,
        'name' => 'Pengumuman',
    ]);
    $kegiatan = PostCategory::factory()->create([
        'team_id' => $lembaga->id,
        'name' => 'Kegiatan',
    ]);
    $post = Post::factory()->create([
        'team_id' => $lembaga->id,
        'category_id' => $pengumuman->id,
    ]);
    Post::factory()->create([
        'team_id' => $lembaga->id,
        'category_id' => $kegiatan->id,
    ]);

    $this->get("/berita?lembaga={$lembaga->slug}&kategori={$pengumuman->slug}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 1)
            ->where('posts.data.0.id', $post->id)
            ->where('filters.kategori', $pengumuman->slug)
        );
});

test('the news index searches posts by title', function () {
    $lembaga = Team::factory()->create();
    Post::factory()->create([
        'team_id' => $lembaga->id,
        'title' => 'Pendaftaran Santri Baru',
    ]);
    Post::factory()->create([
        'team_id' => $lembaga->id,
        'title' => 'Kegiatan Hari Santri',
    ]);

    $this->get('/berita?q=pendaftaran')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 1)
            ->where('posts.data.0.title', 'Pendaftaran Santri Baru')
            ->where('filters.search', 'pendaftaran')
        );
});

test('a published post is viewable on the news show page', function () {
    $post = Post::factory()->create([
        'title' => 'Kegiatan Pesantren Kilat',
        'published_at' => now(),
    ]);

    $this->get("/berita/{$post->slug}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/news-show')
            ->where('post.id', $post->id)
            ->where('post.title', 'Kegiatan Pesantren Kilat')
        );
});

test('a draft post returns 404 on the news show page', function () {
    $post = Post::factory()->draft()->create();

    $this->get("/berita/{$post->slug}")->assertNotFound();
});

test('when two teams share a slug the latest published post is shown', function () {
    $teamA = Team::factory()->create(['name' => 'Pondok A']);
    $teamB = Team::factory()->create(['name' => 'Pondok B']);

    $older = Post::factory()->create([
        'team_id' => $teamA->id,
        'title' => 'Kegiatan Ramadhan',
        'published_at' => now()->subDays(2),
    ]);
    $newer = Post::factory()->create([
        'team_id' => $teamB->id,
        'title' => 'Kegiatan Ramadhan',
        'published_at' => now(),
    ]);

    expect($older->slug)->toBe($newer->slug);

    $this->get("/berita/{$older->slug}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/news-show')
            ->where('post.id', $newer->id));
});

test('the lembaga type label is exposed on public pages', function () {
    $lembaga = Team::factory()->create([
        'type' => LembagaType::PondokPesantren->value,
    ]);

    $this->get('/lembaga')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('lembagas.0.type', 'pondok_pesantren')
            ->where('lembagas.0.type_label', 'Pondok Pesantren'));
});

test('the lembaga index page lists only non personal teams', function () {
    Team::factory()->count(2)->create(['name' => fake()->unique()->company()]);
    Team::factory()->personal()->create(['name' => 'Personal Team']);

    $this->get('/lembaga')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/lembaga')
            ->has('lembagas', 2)
        );
});

test('a public lembaga detail page renders', function () {
    $yayasan = Yayasan::factory()->create();
    $lembaga = Team::factory()->create([
        'name' => 'Pondok Al-Bulmah',
        'yayasan_id' => $yayasan->id,
    ]);
    $post = Post::factory()->create(['team_id' => $lembaga->id]);

    $this->get("/lembaga/{$lembaga->slug}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/lembaga-show')
            ->where('lembaga.id', $lembaga->id)
            ->has('posts', 1)
            ->where('posts.0.title', $post->title)
        );
});

test('a personal team returns 404 on the lembaga detail page', function () {
    $team = Team::factory()->personal()->create();

    $this->get("/lembaga/{$team->slug}")->assertNotFound();
});

test('the about page renders the yayasan profile', function () {
    $yayasan = Yayasan::factory()->create(['name' => 'Yayasan Bulmah']);
    Team::factory()->count(2)->create(['yayasan_id' => $yayasan->id]);

    $this->get('/profil')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/about')
            ->where('yayasan.id', $yayasan->id)
            ->where('yayasan.lembaga_count', 2)
        );
});

test('the about page renders when no yayasan exists yet', function () {
    $this->get('/profil')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/about')
            ->where('yayasan', null)
        );
});
