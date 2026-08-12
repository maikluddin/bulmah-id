<?php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Models\Hafalan;
use App\Models\Santri;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WaliController extends Controller
{
    /**
     * Display the wali portal showing the guardian's santri progress.
     */
    public function index(Request $request, Team $current_team): Response
    {
        Gate::authorize('accessWaliPortal', $current_team);

        $santris = $request->user()->santris()
            ->where('team_id', $current_team->id)
            ->with(['kelas:id,nama'])
            ->orderBy('name')
            ->get()
            ->map(fn (Santri $santri) => [
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
                'summary' => $this->santriSummary($santri),
                'recent' => $this->recentSetoran($santri),
            ]);

        $notifications = $request->user()
            ->notifications()
            ->limit(10)
            ->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'read' => $notification->read_at !== null,
                'created_at' => $notification->created_at->diffForHumans(),
                'data' => $notification->data,
            ]);

        return Inertia::render('wali/index', [
            'team' => [
                'id' => $current_team->id,
                'name' => $current_team->name,
                'slug' => $current_team->slug,
            ],
            'santris' => $santris,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Get the hafalan summary for a santri.
     *
     * @return array{total_hafalan: int, hafalan_baru: int, murajaah: int, bulan_ini: int}
     */
    protected function santriSummary(Santri $santri): array
    {
        return [
            'total_hafalan' => $santri->hafalans()->count(),
            'hafalan_baru' => $santri->hafalans()->where('type', 'hafalan_baru')->count(),
            'murajaah' => $santri->hafalans()->where('type', 'murajaah')->count(),
            'bulan_ini' => $santri->hafalans()->where('tanggal', '>=', now()->startOfMonth())->count(),
        ];
    }

    /**
     * Get the most recent setoran records for a santri.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function recentSetoran(Santri $santri): array
    {
        return $santri->hafalans()
            ->with(['kategori:id,nama', 'ustad:id,name'])
            ->latest('tanggal')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Hafalan $hafalan) => [
                'id' => $hafalan->id,
                'type' => $hafalan->type->value,
                'type_label' => $hafalan->type->label(),
                'juz' => $hafalan->juz,
                'surat' => $hafalan->surat,
                'ayat_from' => $hafalan->ayat_from,
                'ayat_to' => $hafalan->ayat_to,
                'tanggal' => $hafalan->tanggal->format('Y-m-d'),
                'kategori' => $hafalan->kategori ? ['nama' => $hafalan->kategori->nama] : null,
                'ustad' => $hafalan->ustad ? ['name' => $hafalan->ustad->name] : null,
            ])
            ->all();
    }
}
