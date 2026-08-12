<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\ScopesSantri;
use App\Enums\HafalanType;
use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSetoranRequest;
use App\Models\Hafalan;
use App\Models\HafalanKategori;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\Team;
use App\Notifications\HafalanSetoranNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SetoranController extends Controller
{
    use ScopesSantri;

    /**
     * Display the hafalan setoran page (ustad view) for the team's santri.
     */
    public function index(Request $request, Team $current_team): Response
    {
        Gate::authorize('manageSetoranHafalan', $current_team);

        $scope = $request->user()->contentScope($current_team);
        $genderScope = $scope['isAggregate'] ? null : $request->user()->santriGenderFor($current_team);
        $kelasIds = $this->assignedKelasIds($request, $scope['teamIds']);

        $search = $request->string('search')->trim()->toString();

        $santris = Santri::query()
            ->whereIn('team_id', $scope['teamIds'])
            ->when($kelasIds !== null, fn ($query) => $query->whereIn('kelas_id', $kelasIds))
            ->when($genderScope !== null, fn ($query) => $query->where('gender', $genderScope))
            ->when($search !== '', fn ($query) => $query
                ->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")))
            ->with('kelas:id,nama')
            ->orderBy('name')
            ->limit(30)
            ->get()
            ->map(fn (Santri $santri) => [
                'id' => $santri->id,
                'nis' => $santri->nis,
                'name' => $santri->name,
                'gender' => $santri->gender->value,
                'gender_label' => $santri->gender->label(),
                'kelas' => $santri->kelas ? [
                    'id' => $santri->kelas->id,
                    'nama' => $santri->kelas->nama,
                ] : null,
            ]);

        $kategoris = HafalanKategori::query()
            ->whereIn('team_id', $scope['teamIds'])
            ->orderByRaw('CASE WHEN jenis = ? THEN 0 ELSE 1 END', ['alquran'])
            ->orderBy('nama')
            ->get(['id', 'jenis', 'nama'])
            ->map(fn (HafalanKategori $kategori) => [
                'id' => $kategori->id,
                'nama' => $kategori->nama,
                'jenis' => $kategori->jenis->value,
                'jenis_label' => $kategori->jenis->label(),
            ]);

        $recent = Hafalan::query()
            ->whereIn('team_id', $scope['teamIds'])
            ->with(['santri:id,name,nis', 'ustad:id,name', 'kategori:id,nama'])
            ->latest('tanggal')
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (Hafalan $hafalan) => [
                'id' => $hafalan->id,
                'santri' => [
                    'id' => $hafalan->santri->id,
                    'name' => $hafalan->santri->name,
                    'nis' => $hafalan->santri->nis,
                ],
                'type' => $hafalan->type->value,
                'type_label' => $hafalan->type->label(),
                'juz' => $hafalan->juz,
                'surat' => $hafalan->surat,
                'ayat_from' => $hafalan->ayat_from,
                'ayat_to' => $hafalan->ayat_to,
                'tanggal' => $hafalan->tanggal->format('Y-m-d'),
                'kategori' => $hafalan->kategori ? ['nama' => $hafalan->kategori->nama] : null,
                'ustad' => $hafalan->ustad ? ['name' => $hafalan->ustad->name] : null,
            ]);

        return Inertia::render('admin/setoran/index', [
            'team' => $this->toTeamData($current_team),
            'santris' => $santris,
            'recent' => $recent,
            'types' => $this->toEnumOptions(HafalanType::cases()),
            'kategoris' => $kategoris,
            'filters' => compact('search'),
        ]);
    }

    /**
     * Record a new hafalan setoran for a santri.
     */
    public function store(SaveSetoranRequest $request, Team $current_team): RedirectResponse
    {
        Gate::authorize('manageSetoranHafalan', $current_team);

        $santri = $this->findSantriInScope($request, $current_team, $request->integer('santri_id'));

        $hafalan = $santri->hafalans()->create([
            ...$request->validated(),
            'team_id' => $current_team->id,
            'ustad_user_id' => $request->user()->id,
        ]);

        if ($santri->wali_user_id !== null && $santri->wali_user_id !== $request->user()->id) {
            $santri->wali?->notify(new HafalanSetoranNotification($hafalan));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Setoran hafalan berhasil dicatat.')]);

        return back();
    }

    /**
     * Find a santri within the current scope or fail with 404.
     */
    protected function findSantriInScope(Request $request, Team $team, int $id): Santri
    {
        $scope = $request->user()->contentScope($team);
        $kelasIds = $this->assignedKelasIds($request, $scope['teamIds']);

        return Santri::query()
            ->whereIn('team_id', $scope['teamIds'])
            ->when($kelasIds !== null, fn ($query) => $query->whereIn('kelas_id', $kelasIds))
            ->whereKey($id)
            ->firstOrFail();
    }

    /**
     * Get the kelas IDs the user is assigned to as ustad, or null when the
     * user is not restricted to specific kelas (admins, yayasan owners).
     *
     * @param  array<int, int>  $teamIds
     * @return array<int, int>|null
     */
    protected function assignedKelasIds(Request $request, array $teamIds): ?array
    {
        if ($request->user()->teamRole($request->route('current_team')) !== TeamRole::Ustaz) {
            return null;
        }

        return Kelas::query()
            ->whereIn('team_id', $teamIds)
            ->where('ustad_user_id', $request->user()->id)
            ->pluck('id')
            ->all();
    }
}
