<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\ScopesSantri;
use App\Enums\PerizinanStatus;
use App\Http\Controllers\Controller;
use App\Models\HafalanKategori;
use App\Models\Santri;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringController extends Controller
{
    use ScopesSantri;

    /**
     * Display the monitoring hub for a santri.
     */
    public function show(Request $request, Team $current_team, Santri $santri): Response
    {
        Gate::authorize('manageMonitoring', $current_team);
        $this->ensureSantriInScope($current_team, $santri);

        $month = $request->string('month')->trim()->toString();
        $monthDate = $month !== '' ? Carbon::parse($month)->startOfMonth() : now()->startOfMonth();

        $monthRange = [$monthDate->copy()->startOfMonth(), $monthDate->copy()->endOfMonth()];

        $absensiByStatus = $santri->absensis()
            ->whereBetween('tanggal', $monthRange)
            ->get()
            ->groupBy(fn ($absensi) => $absensi->status->value);

        return Inertia::render('admin/monitoring/show', [
            'santri' => [
                'id' => $santri->id,
                'nis' => $santri->nis,
                'name' => $santri->name,
                'gender' => $santri->gender->value,
                'gender_label' => $santri->gender->label(),
                'team' => [
                    'id' => $santri->team_id,
                    'name' => $santri->team->name,
                    'slug' => $santri->team->slug,
                ],
            ],
            'team' => $this->toTeamData($current_team),
            'summary' => [
                'total_hafalan' => $santri->hafalans()->count(),
                'hafalan_bulan_ini' => $santri->hafalans()
                    ->whereBetween('tanggal', $monthRange)
                    ->count(),
                'total_absensi' => $santri->absensis()->count(),
                'absensi_hadir' => $absensiByStatus->get('hadir')?->count() ?? 0,
                'absensi_sakit' => $absensiByStatus->get('sakit')?->count() ?? 0,
                'absensi_izin' => $absensiByStatus->get('izin')?->count() ?? 0,
                'absensi_alpa' => $absensiByStatus->get('alpa')?->count() ?? 0,
                'saldo' => (float) ($santri->wallet?->balance ?? 0),
                'pembayaran_lunas' => (float) $santri->pembayarans()->where('status', 'lunas')->sum('jumlah'),
                'perizinan_pending' => $santri->perizinans()->where('status', PerizinanStatus::Pending->value)->count(),
            ],
            'kategoriProgress' => HafalanKategori::query()
                ->where('team_id', $current_team->id)
                ->withCount(['hafalans' => fn ($query) => $query->where('santri_id', $santri->id)])
                ->orderByRaw('CASE WHEN jenis = ? THEN 0 ELSE 1 END', ['alquran'])
                ->orderBy('nama')
                ->get()
                ->map(fn (HafalanKategori $kategori) => [
                    'id' => $kategori->id,
                    'nama' => $kategori->nama,
                    'jenis' => $kategori->jenis->value,
                    'jenis_label' => $kategori->jenis->label(),
                    'jumlah' => $kategori->hafalans_count,
                ]),
            'month' => $monthDate->format('Y-m'),
        ]);
    }
}
