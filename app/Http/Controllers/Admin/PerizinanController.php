<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\ScopesSantri;
use App\Enums\PerizinanJenis;
use App\Enums\PerizinanStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePerizinanRequest;
use App\Http\Requests\Admin\UpdatePerizinanStatusRequest;
use App\Models\Perizinan;
use App\Models\Santri;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PerizinanController extends Controller
{
    use ScopesSantri;

    /**
     * Display the permits of a santri.
     */
    public function index(Request $request, Team $current_team, Santri $santri): Response
    {
        Gate::authorize('manageMonitoring', $current_team);
        $this->ensureSantriInScope($current_team, $santri);

        $perizinans = $santri->perizinans()
            ->latest('tanggal_mulai')
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Perizinan $perizinan) => [
                'id' => $perizinan->id,
                'jenis' => $perizinan->jenis->value,
                'jenis_label' => $perizinan->jenis->label(),
                'alasan' => $perizinan->alasan,
                'tanggal_mulai' => $perizinan->tanggal_mulai->format('Y-m-d'),
                'tanggal_selesai' => $perizinan->tanggal_selesai?->format('Y-m-d'),
                'status' => $perizinan->status->value,
                'status_label' => $perizinan->status->label(),
                'catatan' => $perizinan->catatan,
                'recorded_by' => $perizinan->recorded_by,
            ]);

        return Inertia::render('admin/perizinan/index', [
            'santri' => $this->toSantri($santri),
            'team' => $this->toTeamData($current_team),
            'perizinans' => $perizinans,
            'jenisOptions' => $this->toEnumOptions(PerizinanJenis::cases()),
            'statusOptions' => $this->toEnumOptions(PerizinanStatus::cases()),
            'pendingCount' => $santri->perizinans()->where('status', PerizinanStatus::Pending->value)->count(),
        ]);
    }

    /**
     * Store a newly recorded permit.
     */
    public function store(SavePerizinanRequest $request, Team $current_team, Santri $santri): RedirectResponse
    {
        Gate::authorize('manageMonitoring', $current_team);
        $this->ensureSantriInScope($current_team, $santri);

        $santri->perizinans()->create([
            ...$request->validated(),
            'team_id' => $current_team->id,
            'recorded_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Izin berhasil dicatat.')]);

        return back();
    }

    /**
     * Update the approval status of a permit.
     */
    public function updateStatus(UpdatePerizinanStatusRequest $request, Team $current_team, Santri $santri, Perizinan $perizinan): RedirectResponse
    {
        Gate::authorize('manageMonitoring', $current_team);
        $this->ensureSantriInScope($current_team, $santri);

        abort_unless($perizinan->santri_id === $santri->id, 404);

        $perizinan->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Status izin berhasil diperbarui.')]);

        return back();
    }

    /**
     * Remove the specified permit.
     */
    public function destroy(Team $current_team, Santri $santri, Perizinan $perizinan): RedirectResponse
    {
        Gate::authorize('manageMonitoring', $current_team);
        $this->ensureSantriInScope($current_team, $santri);

        abort_unless($perizinan->santri_id === $santri->id, 404);

        $perizinan->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Izin berhasil dihapus.')]);

        return back();
    }
}
