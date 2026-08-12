<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class LembagaController extends Controller
{
    public function index(): Response
    {
        $lembagas = Team::query()
            ->where('is_personal', false)
            ->withCount(['santris' => fn (Builder $query) => $query->aktif()])
            ->with('yayasan:id,name,slug')
            ->orderBy('name')
            ->get();

        return Inertia::render('public/lembaga', [
            'lembagas' => $lembagas,
        ]);
    }

    public function show(Team $lembaga): Response
    {
        abort_if($lembaga->is_personal, 404);

        $lembaga->loadCount(['santris' => fn (Builder $query) => $query->aktif()]);
        $lembaga->load('yayasan:id,name,slug');

        $posts = Post::query()
            ->published()
            ->whereBelongsTo($lembaga)
            ->with(['category:id,name'])
            ->latest('published_at')
            ->get();

        return Inertia::render('public/lembaga-show', [
            'lembaga' => $lembaga,
            'posts' => $posts,
        ]);
    }
}
