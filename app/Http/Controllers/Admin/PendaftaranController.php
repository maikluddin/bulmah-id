<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\ScopesSantri;
use App\Enums\SantriStatus;
use App\Http\Controllers\Controller;
use App\Models\Santri;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PendaftaranController extends Controller
{
    use ScopesSantri;

    /**
     * Display the list of calon santri (pendaftar) for the team.
     */
    public function index(Request $request, Team $current_team): Response
    {
        Gate::authorize('manageSantri', $current_team);

        $scope = $request->user()->contentScope($current_team);
        $genderScope = $scope['isAggregate'] ? null : $request->user()->santriGenderFor($current_team);

        $status = $request->string('status')->trim()->toString();
        $search = $request->string('search')->trim()->toString();

        $santris = Santri::query()
            ->whereIn('team_id', $scope['teamIds'])
            ->whereIn('status', [SantriStatus::Pendaftar->value, SantriStatus::Ditolak->value])
            ->with(['team:id,name,slug'])
            ->when($genderScope !== null, fn ($query) => $query->where('gender', $genderScope))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query
                ->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")))
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Santri $santri) => [
                'id' => $santri->id,
                'nisn' => $santri->nisn,
                'name' => $santri->name,
                'gender' => $santri->gender->value,
                'gender_label' => $santri->gender->label(),
                'birth_place' => $santri->birth_place,
                'birth_date' => $santri->birth_date?->format('Y-m-d'),
                'address' => $santri->address,
                'address_province' => $santri->address_province,
                'address_city' => $santri->address_city,
                'address_district' => $santri->address_district,
                'address_village' => $santri->address_village,
                'status' => $santri->status->value,
                'status_label' => $santri->status->label(),
                'ditolak_alasan' => $santri->ditolak_alasan,
                'wali_name' => $santri->wali_name,
                'wali_phone' => $santri->wali_phone,
                'created_at' => $santri->created_at?->format('Y-m-d'),
                'team' => [
                    'id' => $santri->team->id,
                    'name' => $santri->team->name,
                    'slug' => $santri->team->slug,
                ],
            ]);

        $pendingCount = Santri::query()
            ->whereIn('team_id', $scope['teamIds'])
            ->where('status', SantriStatus::Pendaftar->value)
            ->when($genderScope !== null, fn ($query) => $query->where('gender', $genderScope))
            ->count();

        return Inertia::render('admin/pendaftaran/index', [
            'pendaftarans' => $santris,
            'statuses' => [
                ['value' => SantriStatus::Pendaftar->value, 'label' => SantriStatus::Pendaftar->label()],
                ['value' => SantriStatus::Ditolak->value, 'label' => SantriStatus::Ditolak->label()],
            ],
            'filters' => compact('status', 'search'),
            'pendingCount' => $pendingCount,
            'santriScope' => $genderScope?->value,
            'isAggregate' => $scope['isAggregate'],
            'team' => $this->toTeamData($current_team),
        ]);
    }

    /**
     * Accept a calon santri: activate, assign NIS, and create the wallet.
     */
    public function accept(Team $current_team, Santri $santri): RedirectResponse
    {
        Gate::authorize('manageSantri', $current_team);
        $this->ensureSantriInScope($current_team, $santri);

        if ($santri->status !== SantriStatus::Pendaftar) {
            Inertia::flash('toast', ['type' => 'info', 'message' => __('Pendaftaran ini sudah diproses.')]);

            return back();
        }

        $santri->update([
            'status' => SantriStatus::Aktif->value,
            'nis' => $santri->nis ?? Santri::nextNis($current_team),
            'entry_date' => $santri->entry_date ?? now()->format('Y-m-d'),
            'ditolak_alasan' => null,
        ]);

        $santri->wallet()->firstOrCreate();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pendaftaran diterima dan santri berhasil diaktifkan.')]);

        return back();
    }

    /**
     * Reject a calon santri with a reason.
     */
    public function reject(Request $request, Team $current_team, Santri $santri): RedirectResponse
    {
        Gate::authorize('manageSantri', $current_team);
        $this->ensureSantriInScope($current_team, $santri);

        if ($santri->status !== SantriStatus::Pendaftar) {
            Inertia::flash('toast', ['type' => 'info', 'message' => __('Pendaftaran ini sudah diproses.')]);

            return back();
        }

        $validated = $request->validate([
            'alasan' => ['required', 'string', 'max:255'],
        ]);

        $santri->update([
            'status' => SantriStatus::Ditolak->value,
            'ditolak_alasan' => $validated['alasan'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pendaftaran ditolak.')]);

        return back();
    }
}
