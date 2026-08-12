<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\ScopesSantri;
use App\Enums\PembayaranStatus;
use App\Enums\SantriStatus;
use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\Santri;
use App\Models\SantriWallet;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class KeuanganController extends Controller
{
    use ScopesSantri;

    /**
     * Display the keuangan (finance) overview for the team's santri.
     */
    public function index(Request $request, Team $current_team): Response
    {
        Gate::authorize('managePembayaran', $current_team);

        $scope = $request->user()->contentScope($current_team);
        $genderScope = $scope['isAggregate'] ? null : $request->user()->santriGenderFor($current_team);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $santriQuery = Santri::query()
            ->whereIn('team_id', $scope['teamIds'])
            ->when($genderScope !== null, fn ($query) => $query->where('gender', $genderScope));

        $santris = (clone $santriQuery)
            ->with(['team:id,name,slug', 'kelas:id,nama', 'wallet'])
            ->withSum([
                'pembayarans as pembayaran_lunas_total' => fn ($query) => $query
                    ->where('status', PembayaranStatus::Lunas->value),
                'pembayarans as pembayaran_pending_total' => fn ($query) => $query
                    ->where('status', PembayaranStatus::Pending->value),
            ], 'jumlah')
            ->when($search !== '', fn ($query) => $query
                ->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Santri $santri) => [
                'id' => $santri->id,
                'nis' => $santri->nis,
                'name' => $santri->name,
                'gender' => $santri->gender->value,
                'gender_label' => $santri->gender->label(),
                'status' => $santri->status->value,
                'status_label' => $santri->status->label(),
                'kelas' => $santri->kelas ? [
                    'id' => $santri->kelas->id,
                    'nama' => $santri->kelas->nama,
                ] : null,
                'saldo' => (float) ($santri->wallet?->balance ?? 0),
                'pembayaran_lunas' => (float) $santri->pembayaran_lunas_total,
                'pembayaran_pending' => (float) $santri->pembayaran_pending_total,
                'team' => [
                    'id' => $santri->team->id,
                    'name' => $santri->team->name,
                    'slug' => $santri->team->slug,
                ],
            ]);

        $pembayaranQuery = Pembayaran::query()
            ->whereIn('team_id', $scope['teamIds'])
            ->when($genderScope !== null, fn ($query) => $query->whereHas('santri', fn ($query) => $query->where('gender', $genderScope)));

        $summary = [
            'total_santri' => (clone $santriQuery)->count(),
            'santri_aktif' => (clone $santriQuery)->where('status', SantriStatus::Aktif->value)->count(),
            'saldo_dompet' => (float) SantriWallet::query()
                ->whereIn('santri_id', (clone $santriQuery)->select('id'))
                ->sum('balance'),
            'pembayaran_lunas' => (float) (clone $pembayaranQuery)
                ->where('status', PembayaranStatus::Lunas->value)
                ->sum('jumlah'),
            'pembayaran_pending' => (float) (clone $pembayaranQuery)
                ->where('status', PembayaranStatus::Pending->value)
                ->sum('jumlah'),
        ];

        return Inertia::render('admin/keuangan/index', [
            'santris' => $santris,
            'summary' => $summary,
            'statuses' => $this->toEnumOptions(SantriStatus::cases()),
            'filters' => compact('search', 'status'),
            'santriScope' => $genderScope?->value,
            'isAggregate' => $scope['isAggregate'],
            'team' => $this->toTeamData($current_team),
        ]);
    }
}
