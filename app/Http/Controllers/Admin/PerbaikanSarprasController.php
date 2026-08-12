<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\ScopesSantri;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePerbaikanSarprasRequest;
use App\Http\Requests\Admin\UpdatePerbaikanSarprasStatusRequest;
use App\Models\PerbaikanSarpras;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PerbaikanSarprasController extends Controller
{
    use ScopesSantri;

    /**
     * Report a new perbaikan request for a sarpras item.
     */
    public function store(SavePerbaikanSarprasRequest $request, Team $current_team): RedirectResponse
    {
        Gate::authorize('manageSarpras', $current_team);

        $current_team->perbaikanSarpras()->create([
            ...$request->validated(),
            'pelapor_user_id' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Laporan kerusakan berhasil dikirim.')]);

        return back();
    }

    /**
     * Update the status of a perbaikan request.
     */
    public function updateStatus(UpdatePerbaikanSarprasStatusRequest $request, Team $current_team, PerbaikanSarpras $perbaikan): RedirectResponse
    {
        Gate::authorize('manageSarpras', $current_team);
        abort_unless($perbaikan->team_id === $current_team->id, 404);

        $perbaikan->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Status perbaikan berhasil diperbarui.')]);

        return back();
    }

    /**
     * Remove the specified perbaikan request.
     */
    public function destroy(Team $current_team, PerbaikanSarpras $perbaikan): RedirectResponse
    {
        Gate::authorize('manageSarpras', $current_team);
        abort_unless($perbaikan->team_id === $current_team->id, 404);

        $perbaikan->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Laporan kerusakan berhasil dihapus.')]);

        return back();
    }
}
