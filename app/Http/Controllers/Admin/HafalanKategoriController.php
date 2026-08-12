<?php

namespace App\Http\Controllers\Admin;

use App\Enums\HafalanKategoriJenis;
use App\Enums\LembagaJenjang;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveHafalanKategoriRequest;
use App\Models\HafalanKategori;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class HafalanKategoriController extends Controller
{
    /**
     * Display a listing of the team's hafalan categories.
     */
    public function index(Request $request, Team $current_team): Response
    {
        Gate::authorize('manageMonitoring', $current_team);

        $jenis = $request->string('jenis')->trim()->toString();

        $kategoris = HafalanKategori::query()
            ->where('team_id', $current_team->id)
            ->withCount('hafalans')
            ->when($jenis !== '', fn ($query) => $query->where('jenis', $jenis))
            ->orderByRaw('CASE WHEN jenis = ? THEN 0 ELSE 1 END', ['alquran'])
            ->orderBy('nama')
            ->get()
            ->map(fn (HafalanKategori $kategori) => [
                'id' => $kategori->id,
                'nama' => $kategori->nama,
                'jenis' => $kategori->jenis->value,
                'jenis_label' => $kategori->jenis->label(),
                'hafalans_count' => $kategori->hafalans_count,
            ]);

        return Inertia::render('admin/hafalan-kategori/index', [
            'kategoris' => $kategoris,
            'jenisOptions' => array_map(
                fn (HafalanKategoriJenis $jenis) => ['value' => $jenis->value, 'label' => $jenis->label()],
                HafalanKategoriJenis::cases(),
            ),
            'filters' => ['jenis' => $jenis],
            'jenjang' => $current_team->jenjang,
            'templateKategoris' => collect(LembagaJenjang::tryFrom($current_team->jenjang)?->defaultHafalanKategoris() ?? [])
                ->map(fn (array $item) => [
                    'jenis' => $item['jenis'],
                    'jenis_label' => HafalanKategoriJenis::tryFrom($item['jenis'])?->label(),
                    'nama' => $item['nama'],
                ])
                ->values()
                ->all(),
            'team' => [
                'id' => $current_team->id,
                'name' => $current_team->name,
                'slug' => $current_team->slug,
                'jenjang' => $current_team->jenjang,
            ],
        ]);
    }

    /**
     * Store the default kurikulum categories for the team's jenjang.
     */
    public function storeTemplate(Request $request, Team $current_team): RedirectResponse
    {
        Gate::authorize('manageMonitoring', $current_team);

        $jenjang = $current_team->jenjang !== null
            ? LembagaJenjang::tryFrom($current_team->jenjang)
            : null;

        $template = $jenjang?->defaultHafalanKategoris() ?? [];

        if ($template === []) {
            Inertia::flash('toast', ['type' => 'info', 'message' => __('Tidak ada template kurikulum untuk lembaga ini.')]);

            return back();
        }

        $created = 0;

        foreach ($template as $item) {
            $kategori = $current_team->hafalanKategoris()
                ->firstOrCreate([
                    'jenis' => $item['jenis'],
                    'nama' => $item['nama'],
                ]);

            if ($kategori->wasRecentlyCreated) {
                $created++;
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $created > 0
            ? __('Template kurikulum ditambahkan (:count kategori).', ['count' => $created])
            : __('Semua kategori template sudah tersedia.')]);

        return back();
    }

    /**
     * Store a newly created hafalan category.
     */
    public function store(SaveHafalanKategoriRequest $request, Team $current_team): RedirectResponse
    {
        Gate::authorize('manageMonitoring', $current_team);

        $current_team->hafalanKategoris()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Kategori hafalan berhasil dibuat.')]);

        return back();
    }

    /**
     * Update the specified hafalan category.
     */
    public function update(SaveHafalanKategoriRequest $request, Team $current_team, HafalanKategori $kategori): RedirectResponse
    {
        Gate::authorize('manageMonitoring', $current_team);
        abort_unless($kategori->team_id === $current_team->id, 404);

        $kategori->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Kategori hafalan berhasil diperbarui.')]);

        return back();
    }

    /**
     * Remove the specified hafalan category.
     */
    public function destroy(Team $current_team, HafalanKategori $kategori): RedirectResponse
    {
        Gate::authorize('manageMonitoring', $current_team);
        abort_unless($kategori->team_id === $current_team->id, 404);

        if ($kategori->hafalans()->exists()) {
            throw ValidationException::withMessages([
                'kategori' => __('Kategori yang masih memiliki catatan hafalan tidak dapat dihapus.'),
            ]);
        }

        $kategori->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Kategori hafalan berhasil dihapus.')]);

        return back();
    }
}
