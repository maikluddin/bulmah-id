<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    public function index(Request $request): Response
    {
        $lembaga = $request->string('lembaga')->trim()->toString();
        $kategori = $request->string('kategori')->trim()->toString();
        $search = $request->string('q')->trim()->toString();

        $posts = Post::query()
            ->published()
            ->with(['team:id,name,slug', 'category:id,name'])
            ->when($lembaga !== '', fn (Builder $query) => $query->whereHas(
                'team',
                fn (Builder $teamQuery) => $teamQuery->where('slug', $lembaga),
            ))
            ->when($kategori !== '', fn (Builder $query) => $query->whereHas(
                'category',
                fn (Builder $categoryQuery) => $categoryQuery->where('slug', $kategori),
            ))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search) {
                $query->whereLike('title', "%{$search}%")
                    ->orWhereLike('excerpt', "%{$search}%");
            }))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        $lembagas = Team::query()
            ->where('is_personal', false)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $categories = PostCategory::query()
            ->whereHas('team', fn (Builder $query) => $query->where('is_personal', false))
            ->with('team:id,name,slug')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'team_id'])
            ->map(fn (PostCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'team_slug' => $category->team->slug,
            ]);

        return Inertia::render('public/news', [
            'posts' => $posts,
            'lembagas' => $lembagas,
            'categories' => $categories,
            'filters' => compact('lembaga', 'kategori', 'search'),
        ]);
    }

    public function show(string $slug): Response
    {
        $post = Post::query()
            ->published()
            ->where('slug', $slug)
            ->latest('published_at')
            ->firstOrFail();

        $post->load(['team:id,name,slug', 'category:id,name', 'author:id,name']);

        $related = Post::query()
            ->published()
            ->whereNot('id', $post->id)
            ->whereBelongsTo($post->team)
            ->with(['team:id,name,slug', 'category:id,name'])
            ->latest('published_at')
            ->limit(3)
            ->get();

        return Inertia::render('public/news-show', [
            'post' => $post,
            'related' => $related,
        ]);
    }
}
