<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Yayasan;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class AboutController extends Controller
{
    public function __invoke(): Response
    {
        $yayasan = Yayasan::query()
            ->withCount(['lembaga' => fn (Builder $query) => $query->where('is_personal', false)])
            ->first();

        return Inertia::render('public/about', [
            'yayasan' => $yayasan,
        ]);
    }
}
