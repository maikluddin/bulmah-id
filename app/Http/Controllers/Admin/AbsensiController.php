<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\ScopesSantri;
use App\Enums\AbsensiStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveAbsensiRequest;
use App\Models\Absensi;
use App\Models\Santri;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AbsensiController extends Controller
{
    use ScopesSantri;

    /**
     * Display the attendance records for a santri.
     */
    public function index(Request $request, Team $current_team, Santri $santri): Response
    {
        Gate::authorize('manageMonitoring', $current_team);
        $this->ensureSantriInScope($current_team, $santri);

        $month = $request->string('month')->trim()->toString();
        $monthDate = $month !== '' ? Carbon::parse($month)->startOfMonth() : now()->startOfMonth();
        $monthRange = [$monthDate->copy()->startOfMonth(), $monthDate->copy()->endOfMonth()];

        $records = $santri->absensis()
            ->whereBetween('tanggal', $monthRange)
            ->latest('tanggal')
            ->latest('id')
            ->get()
            ->map(fn (Absensi $absensi) => [
                'id' => $absensi->id,
                'tanggal' => $absensi->tanggal->format('Y-m-d'),
                'status' => $absensi->status->value,
                'status_label' => $absensi->status->label(),
                'keterangan' => $absensi->keterangan,
            ]);

        $byStatus = $records->groupBy('status');

        return Inertia::render('admin/absensi/index', [
            'santri' => $this->toSantri($santri),
            'team' => $this->toTeamData($current_team),
            'records' => $records,
            'month' => $monthDate->format('Y-m'),
            'stats' => [
                'hadir' => $byStatus->get('hadir')?->count() ?? 0,
                'sakit' => $byStatus->get('sakit')?->count() ?? 0,
                'izin' => $byStatus->get('izin')?->count() ?? 0,
                'alpa' => $byStatus->get('alpa')?->count() ?? 0,
            ],
            'statuses' => $this->toEnumOptions(AbsensiStatus::cases()),
        ]);
    }

    /**
     * Store (or update) an attendance record for a santri on a given date.
     */
    public function store(SaveAbsensiRequest $request, Team $current_team, Santri $santri): RedirectResponse
    {
        Gate::authorize('manageMonitoring', $current_team);
        $this->ensureSantriInScope($current_team, $santri);

        $santri->absensis()->updateOrCreate(
            ['tanggal' => $request->date('tanggal')],
            [
                ...$request->safe(['status', 'keterangan']),
                'team_id' => $current_team->id,
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Absensi berhasil dicatat.')]);

        return back();
    }

    /**
     * Remove the specified attendance record.
     */
    public function destroy(Team $current_team, Santri $santri, Absensi $absensi): RedirectResponse
    {
        Gate::authorize('manageMonitoring', $current_team);
        $this->ensureSantriInScope($current_team, $santri);

        abort_unless($absensi->santri_id === $santri->id, 404);

        $absensi->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Absensi berhasil dihapus.')]);

        return back();
    }
}
