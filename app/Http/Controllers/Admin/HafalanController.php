<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\ScopesSantri;
use App\Enums\HafalanType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveHafalanRequest;
use App\Models\Hafalan;
use App\Models\HafalanKategori;
use App\Models\Santri;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HafalanController extends Controller
{
    use ScopesSantri;

    /**
     * Display the hafalan records for a santri.
     */
    public function index(Request $request, Team $current_team, Santri $santri): Response
    {
        Gate::authorize('manageMonitoring', $current_team);
        $this->ensureSantriInScope($current_team, $santri);

        $search = $request->string('search')->trim()->toString();
        $type = $request->string('type')->trim()->toString();
        $kategori = $request->string('kategori')->trim()->toString();

        $kategoris = HafalanKategori::query()
            ->where('team_id', $current_team->id)
            ->orderByRaw('CASE WHEN jenis = ? THEN 0 ELSE 1 END', ['alquran'])
            ->orderBy('nama')
            ->get(['id', 'jenis', 'nama'])
            ->map(fn (HafalanKategori $kategori) => [
                'value' => (string) $kategori->id,
                'label' => $kategori->nama,
                'jenis' => $kategori->jenis->value,
                'jenis_label' => $kategori->jenis->label(),
            ]);

        $hafalans = $santri->hafalans()
            ->with(['ustad:id,name', 'kategori:id,nama'])
            ->when($search !== '', fn ($query) => $query->where('surat', 'like', "%{$search}%"))
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->when($kategori !== '', fn ($query) => $query->where('kategori_id', $kategori))
            ->latest('tanggal')
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Hafalan $hafalan) => [
                'id' => $hafalan->id,
                'type' => $hafalan->type->value,
                'type_label' => $hafalan->type->label(),
                'juz' => $hafalan->juz,
                'surat' => $hafalan->surat,
                'ayat_from' => $hafalan->ayat_from,
                'ayat_to' => $hafalan->ayat_to,
                'tanggal' => $hafalan->tanggal->format('Y-m-d'),
                'keterangan' => $hafalan->keterangan,
                'kategori' => $hafalan->kategori ? [
                    'id' => $hafalan->kategori->id,
                    'nama' => $hafalan->kategori->nama,
                ] : null,
                'ustad' => $hafalan->ustad ? [
                    'id' => $hafalan->ustad->id,
                    'name' => $hafalan->ustad->name,
                ] : null,
            ]);

        return Inertia::render('admin/hafalan/index', [
            'santri' => $this->toSantri($santri),
            'team' => $this->toTeamData($current_team),
            'hafalans' => $hafalans,
            'types' => $this->toEnumOptions(HafalanType::cases()),
            'kategoris' => $kategoris,
            'filters' => compact('search', 'type', 'kategori'),
        ]);
    }

    /**
     * Store a newly recorded hafalan entry.
     */
    public function store(SaveHafalanRequest $request, Team $current_team, Santri $santri): RedirectResponse
    {
        Gate::authorize('manageMonitoring', $current_team);
        $this->ensureSantriInScope($current_team, $santri);

        $santri->hafalans()->create([
            ...$request->validated(),
            'team_id' => $current_team->id,
            'ustad_user_id' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Hafalan berhasil dicatat.')]);

        return to_route('admin.santri.hafalan.index', [
            'current_team' => $current_team->slug,
            'santri' => $santri->id,
        ]);
    }

    /**
     * Remove the specified hafalan entry.
     */
    public function destroy(Team $current_team, Santri $santri, Hafalan $hafalan): RedirectResponse
    {
        Gate::authorize('manageMonitoring', $current_team);
        $this->ensureSantriInScope($current_team, $santri);

        abort_unless($hafalan->santri_id === $santri->id, 404);

        $hafalan->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Hafalan berhasil dihapus.')]);

        return to_route('admin.santri.hafalan.index', [
            'current_team' => $current_team->slug,
            'santri' => $santri->id,
        ]);
    }
}
