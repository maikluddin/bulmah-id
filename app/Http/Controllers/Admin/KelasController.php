<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveKelasRequest;
use App\Models\Kelas;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class KelasController extends Controller
{
    /**
     * Display a listing of the team's kelas.
     */
    public function index(Team $current_team): Response
    {
        Gate::authorize('manageSantri', $current_team);

        $kelas = Kelas::query()
            ->where('team_id', $current_team->id)
            ->with(['ustad:id,name'])
            ->withCount('santris')
            ->orderBy('nama')
            ->get()
            ->map(fn (Kelas $kelas) => [
                'id' => $kelas->id,
                'nama' => $kelas->nama,
                'santris_count' => $kelas->santris_count,
                'ustad' => $kelas->ustad ? [
                    'id' => $kelas->ustad->id,
                    'name' => $kelas->ustad->name,
                ] : null,
            ]);

        $ustads = $current_team->members()
            ->orderBy('name')
            ->get(['users.id', 'users.name'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ]);

        return Inertia::render('admin/kelas/index', [
            'kelas' => $kelas,
            'ustads' => $ustads,
            'team' => [
                'id' => $current_team->id,
                'name' => $current_team->name,
                'slug' => $current_team->slug,
            ],
        ]);
    }

    /**
     * Store a newly created kelas.
     */
    public function store(SaveKelasRequest $request, Team $current_team): RedirectResponse
    {
        Gate::authorize('manageSantri', $current_team);

        $current_team->kelas()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Kelas berhasil dibuat.')]);

        return back();
    }

    /**
     * Update the specified kelas.
     */
    public function update(SaveKelasRequest $request, Team $current_team, Kelas $kelas): RedirectResponse
    {
        Gate::authorize('manageSantri', $current_team);
        abort_unless($kelas->team_id === $current_team->id, 404);

        $kelas->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Kelas berhasil diperbarui.')]);

        return back();
    }

    /**
     * Remove the specified kelas.
     */
    public function destroy(Team $current_team, Kelas $kelas): RedirectResponse
    {
        Gate::authorize('manageSantri', $current_team);
        abort_unless($kelas->team_id === $current_team->id, 404);

        if ($kelas->santris()->exists()) {
            throw ValidationException::withMessages([
                'kelas' => __('Kelas yang masih memiliki santri tidak dapat dihapus.'),
            ]);
        }

        $kelas->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Kelas berhasil dihapus.')]);

        return back();
    }
}
