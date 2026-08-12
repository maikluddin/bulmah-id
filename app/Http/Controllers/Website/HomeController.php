<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Team;
use App\Models\Yayasan;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $yayasan = Yayasan::query()
            ->withCount(['lembaga' => fn (Builder $query) => $query->where('is_personal', false)])
            ->first();

        $lembagas = Team::query()
            ->where('is_personal', false)
            ->withCount(['santris' => fn (Builder $query) => $query->aktif()])
            ->orderBy('name')
            ->get();

        $posts = Post::query()
            ->published()
            ->with(['team:id,name,slug', 'category:id,name'])
            ->latest('published_at')
            ->limit(3)
            ->get();

        return Inertia::render('public/home', [
            'yayasan' => $yayasan,
            'lembagas' => $lembagas,
            'posts' => $posts,
            'stats' => [
                'lembaga' => $lembagas->count(),
                'santri' => $lembagas->sum('santris_count'),
                'post' => Post::query()->published()->count(),
            ],
        ]);
    }
}
