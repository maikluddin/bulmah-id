<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\ScopesSantri;
use App\Enums\PembayaranJenis;
use App\Enums\PembayaranMetode;
use App\Enums\PembayaranStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePembayaranRequest;
use App\Models\Pembayaran;
use App\Models\Santri;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PembayaranController extends Controller
{
    use ScopesSantri;

    /**
     * Display the payment records for a santri.
     */
    public function index(Request $request, Team $current_team, Santri $santri): Response
    {
        Gate::authorize('managePembayaran', $current_team);
        $this->ensureSantriInScope($current_team, $santri);

        $pembayarans = $santri->pembayarans()
            ->latest('tanggal')
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Pembayaran $pembayaran) => [
                'id' => $pembayaran->id,
                'jenis' => $pembayaran->jenis->value,
                'jenis_label' => $pembayaran->jenis->label(),
                'jumlah' => (float) $pembayaran->jumlah,
                'tanggal' => $pembayaran->tanggal->format('Y-m-d'),
                'metode' => $pembayaran->metode->value,
                'metode_label' => $pembayaran->metode->label(),
                'status' => $pembayaran->status->value,
                'status_label' => $pembayaran->status->label(),
                'keterangan' => $pembayaran->keterangan,
            ]);

        return Inertia::render('admin/pembayaran/index', [
            'santri' => $this->toSantri($santri),
            'team' => $this->toTeamData($current_team),
            'pembayarans' => $pembayarans,
            'total_lunas' => (float) $santri->pembayarans()->where('status', PembayaranStatus::Lunas->value)->sum('jumlah'),
            'total_pending' => (float) $santri->pembayarans()->where('status', PembayaranStatus::Pending->value)->sum('jumlah'),
            'jenisOptions' => $this->toEnumOptions(PembayaranJenis::cases()),
            'metodeOptions' => $this->toEnumOptions(PembayaranMetode::cases()),
            'statusOptions' => $this->toEnumOptions(PembayaranStatus::cases()),
        ]);
    }

    /**
     * Store a newly recorded payment.
     */
    public function store(SavePembayaranRequest $request, Team $current_team, Santri $santri): RedirectResponse
    {
        Gate::authorize('managePembayaran', $current_team);
        $this->ensureSantriInScope($current_team, $santri);

        $santri->pembayarans()->create([
            ...$request->validated(),
            'team_id' => $current_team->id,
            'recorded_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pembayaran berhasil dicatat.')]);

        return back();
    }

    /**
     * Remove the specified payment record.
     */
    public function destroy(Team $current_team, Santri $santri, Pembayaran $pembayaran): RedirectResponse
    {
        Gate::authorize('managePembayaran', $current_team);
        $this->ensureSantriInScope($current_team, $santri);

        abort_unless($pembayaran->santri_id === $santri->id, 404);

        $pembayaran->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pembayaran berhasil dihapus.')]);

        return back();
    }
}
