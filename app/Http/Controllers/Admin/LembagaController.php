<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LembagaJenjang;
use App\Enums\LembagaType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveLembagaRequest;
use App\Models\Team;
use App\Models\Yayasan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LembagaController extends Controller
{
    /**
     * Display a listing of the lembaga the user can manage.
     */
    public function index(Team $current_team): Response
    {
        Gate::authorize('manageLembaga', $current_team);

        $scope = request()->user()->contentScope($current_team);

        $lembagas = Team::query()
            ->whereIn('id', $scope['teamIds'])
            ->withCount(['posts', 'santris'])
            ->orderBy('name')
            ->get()
            ->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'type' => $team->type,
                'type_label' => $team->type_label,
                'jenjang' => $team->jenjang,
                'jenjang_label' => $team->jenjang_label,
                'description' => $team->description,
                'address' => $team->address,
                'phone' => $team->phone,
                'email' => $team->email,
                'yayasan_id' => $team->yayasan_id,
                'is_personal' => $team->is_personal,
                'posts_count' => $team->posts_count,
                'santris_count' => $team->santris_count,
            ]);

        return Inertia::render('admin/lembaga/index', [
            'lembagas' => $lembagas,
            'isAggregate' => $scope['isAggregate'],
            'team' => [
                'id' => $current_team->id,
                'name' => $current_team->name,
                'slug' => $current_team->slug,
            ],
        ]);
    }

    /**
     * Show the lembaga (team) public profile form.
     */
    public function edit(Team $current_team): Response
    {
        Gate::authorize('manageLembaga', $current_team);

        return Inertia::render('admin/lembaga/profile', [
            'team' => [
                'id' => $current_team->id,
                'name' => $current_team->name,
                'slug' => $current_team->slug,
                'type' => $current_team->type,
                'type_label' => $current_team->type_label,
                'jenjang' => $current_team->jenjang,
                'jenjang_label' => $current_team->jenjang_label,
                'description' => $current_team->description,
                'address' => $current_team->address,
                'phone' => $current_team->phone,
                'email' => $current_team->email,
                'yayasan_id' => $current_team->yayasan_id,
                'is_personal' => $current_team->is_personal,
            ],
            'types' => array_map(
                fn (LembagaType $type) => ['value' => $type->value, 'label' => $type->label()],
                LembagaType::cases(),
            ),
            'jenjangs' => array_map(
                fn (LembagaJenjang $jenjang) => ['value' => $jenjang->value, 'label' => $jenjang->label()],
                LembagaJenjang::cases(),
            ),
            'yayasans' => Yayasan::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Yayasan $yayasan) => ['id' => $yayasan->id, 'name' => $yayasan->name])
                ->all(),
        ]);
    }

    /**
     * Update the lembaga (team) public profile.
     */
    public function update(SaveLembagaRequest $request, Team $current_team): RedirectResponse
    {
        Gate::authorize('manageLembaga', $current_team);

        $current_team->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profil lembaga berhasil diperbarui.')]);

        return back();
    }
}
