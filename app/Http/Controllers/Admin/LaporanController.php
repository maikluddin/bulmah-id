<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\ScopesSantri;
use App\Http\Controllers\Controller;
use App\Models\Hafalan;
use App\Models\HafalanKategori;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LaporanController extends Controller
{
    use ScopesSantri;

    /**
     * Display the setoran recap (laporan) for the team.
     */
    public function setoran(Request $request, Team $current_team): Response
    {
        Gate::authorize('manageMonitoring', $current_team);

        $scope = $request->user()->contentScope($current_team);
        $teamIds = $scope['teamIds'];

        $from = $request->string('from')->trim()->toString();
        $to = $request->string('to')->trim()->toString();
        $kategori = $request->string('kategori')->trim()->toString();
        $search = $request->string('search')->trim()->toString();

        $fromDate = $from !== '' ? Carbon::parse($from)->startOfDay() : now()->startOfMonth();
        $toDate = $to !== '' ? Carbon::parse($to)->endOfDay() : now()->endOfDay();

        $kategoris = HafalanKategori::query()
            ->whereIn('team_id', $teamIds)
            ->orderByRaw('CASE WHEN jenis = ? THEN 0 ELSE 1 END', ['alquran'])
            ->orderBy('nama')
            ->get(['id', 'jenis', 'nama'])
            ->map(fn (HafalanKategori $kategori) => [
                'id' => $kategori->id,
                'nama' => $kategori->nama,
                'jenis' => $kategori->jenis->value,
                'jenis_label' => $kategori->jenis->label(),
            ]);

        $query = Hafalan::query()
            ->whereIn('team_id', $teamIds)
            ->whereBetween('tanggal', [$fromDate, $toDate])
            ->when($kategori !== '', fn ($query) => $query->where('kategori_id', $kategori))
            ->when($search !== '', fn ($query) => $query->whereHas('santri', fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('nis', 'like', "%{$search}%")));

        $records = (clone $query)
            ->with(['santri:id,name,nis', 'kategori:id,nama', 'ustad:id,name'])
            ->latest('tanggal')
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Hafalan $hafalan) => [
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
                'kategori' => $hafalan->kategori
                    ? ['id' => $hafalan->kategori->id, 'nama' => $hafalan->kategori->nama]
                    : null,
                'ustad' => $hafalan->ustad ? ['name' => $hafalan->ustad->name] : null,
            ]);

        $perKategori = (clone $query)
            ->selectRaw('kategori_id, COUNT(*) as jumlah')
            ->groupBy('kategori_id')
            ->with('kategori:id,nama')
            ->get()
            ->map(fn (Hafalan $hafalan) => [
                'id' => $hafalan->kategori?->id,
                'nama' => $hafalan->kategori?->nama ?? 'Tanpa Kategori',
                'jumlah' => $hafalan->jumlah,
            ])
            ->sortByDesc('jumlah')
            ->values();

        $perSantri = (clone $query)
            ->selectRaw('santri_id, COUNT(*) as jumlah')
            ->groupBy('santri_id')
            ->with('santri:id,name,nis')
            ->orderByDesc('jumlah')
            ->limit(5)
            ->get()
            ->map(fn (Hafalan $hafalan) => [
                'id' => $hafalan->santri->id,
                'name' => $hafalan->santri->name,
                'nis' => $hafalan->santri->nis,
                'jumlah' => $hafalan->jumlah,
            ]);

        $summary = [
            'total' => (clone $query)->count(),
            'santri' => (clone $query)->distinct()->count('santri_id'),
            'kategori_terpakai' => $perKategori->count(),
        ];

        return Inertia::render('admin/laporan/setoran', [
            'team' => $this->toTeamData($current_team),
            'records' => $records,
            'summary' => $summary,
            'perKategori' => $perKategori,
            'perSantri' => $perSantri,
            'kategoris' => $kategoris,
            'filters' => compact('from', 'to', 'kategori', 'search'),
        ]);
    }
}
