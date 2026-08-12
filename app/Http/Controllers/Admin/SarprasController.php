<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\ScopesSantri;
use App\Enums\PerbaikanStatus;
use App\Enums\SarprasKategori;
use App\Enums\SarprasKondisi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSarprasItemRequest;
use App\Models\PerbaikanSarpras;
use App\Models\SarprasItem;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SarprasController extends Controller
{
    use ScopesSantri;

    /**
     * Display the e-sarpras inventory and perbaikan requests.
     */
    public function index(Request $request, Team $current_team): Response
    {
        Gate::authorize('manageSarpras', $current_team);

        $items = SarprasItem::query()
            ->where('team_id', $current_team->id)
            ->withCount(['perbaikans' => fn ($query) => $query->where('status', '!=', PerbaikanStatus::Selesai->value)])
            ->orderBy('nama')
            ->get()
            ->map(fn (SarprasItem $item) => [
                'id' => $item->id,
                'nama' => $item->nama,
                'kategori' => $item->kategori->value,
                'kategori_label' => $item->kategori->label(),
                'lokasi' => $item->lokasi,
                'jumlah' => $item->jumlah,
                'kondisi' => $item->kondisi->value,
                'kondisi_label' => $item->kondisi->label(),
                'keterangan' => $item->keterangan,
                'foto_path' => $item->foto_path,
                'foto_url' => $this->fotoUrl($item->foto_path),
                'perbaikans_aktif' => $item->perbaikans_count,
            ]);

        $perbaikans = PerbaikanSarpras::query()
            ->where('team_id', $current_team->id)
            ->with(['item:id,nama', 'pelapor:id,name'])
            ->latest('created_at')
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (PerbaikanSarpras $perbaikan) => [
                'id' => $perbaikan->id,
                'item' => $perbaikan->item ? ['id' => $perbaikan->item->id, 'nama' => $perbaikan->item->nama] : null,
                'deskripsi' => $perbaikan->deskripsi,
                'status' => $perbaikan->status->value,
                'status_label' => $perbaikan->status->label(),
                'catatan' => $perbaikan->catatan,
                'pelapor' => $perbaikan->pelapor ? ['id' => $perbaikan->pelapor->id, 'name' => $perbaikan->pelapor->name] : null,
                'created_at' => $perbaikan->created_at?->diffForHumans(),
            ]);

        return Inertia::render('admin/sarpras/index', [
            'team' => $this->toTeamData($current_team),
            'items' => $items,
            'perbaikans' => $perbaikans,
            'summary' => [
                'total_items' => $items->count(),
                'total_jumlah' => SarprasItem::where('team_id', $current_team->id)->sum('jumlah'),
                'rusak_aktif' => $perbaikans->whereIn('status', [PerbaikanStatus::Pending->value, PerbaikanStatus::Diproses->value])->count(),
                'barang_rusak' => SarprasItem::where('team_id', $current_team->id)->where('kondisi', '!=', SarprasKondisi::Baik->value)->count(),
            ],
            'kategoriOptions' => $this->toEnumOptions(SarprasKategori::cases()),
            'kondisiOptions' => $this->toEnumOptions(SarprasKondisi::cases()),
            'statusOptions' => $this->toEnumOptions(PerbaikanStatus::cases()),
        ]);
    }

    /**
     * Store a newly created sarpras item.
     */
    public function store(SaveSarprasItemRequest $request, Team $current_team): RedirectResponse
    {
        Gate::authorize('manageSarpras', $current_team);

        $data = $this->applyFoto($request, null, $request->validated());

        $current_team->sarprasItems()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Inventaris berhasil ditambahkan.')]);

        return back();
    }

    /**
     * Update the specified sarpras item.
     */
    public function update(SaveSarprasItemRequest $request, Team $current_team, SarprasItem $sarpras_item): RedirectResponse
    {
        Gate::authorize('manageSarpras', $current_team);
        abort_unless($sarpras_item->team_id === $current_team->id, 404);

        $data = $this->applyFoto($request, $sarpras_item, $request->validated());

        $sarpras_item->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Inventaris berhasil diperbarui.')]);

        return back();
    }

    /**
     * Remove the specified sarpras item.
     */
    public function destroy(Team $current_team, SarprasItem $sarpras_item): RedirectResponse
    {
        Gate::authorize('manageSarpras', $current_team);
        abort_unless($sarpras_item->team_id === $current_team->id, 404);

        $this->deleteFoto($sarpras_item->foto_path);

        $sarpras_item->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Inventaris berhasil dihapus.')]);

        return back();
    }

    /**
     * Merge foto upload handling into the validated payload.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function applyFoto(SaveSarprasItemRequest $request, ?SarprasItem $item, array $data): array
    {
        unset($data['foto'], $data['remove_foto']);

        if ($request->boolean('remove_foto') && $item?->foto_path) {
            $this->deleteFoto($item->foto_path);
            $data['foto_path'] = null;
        }

        if ($request->hasFile('foto')) {
            if ($item?->foto_path) {
                $this->deleteFoto($item->foto_path);
            }

            $data['foto_path'] = $request->file('foto')->store('sarpras', 'public');
        }

        return $data;
    }

    /**
     * Delete a stored item foto, if any.
     */
    protected function deleteFoto(?string $fotoPath): void
    {
        if ($fotoPath) {
            Storage::disk('public')->delete($fotoPath);
        }
    }

    /**
     * Get the public URL for a stored foto, if any.
     */
    protected function fotoUrl(?string $fotoPath): ?string
    {
        return $fotoPath
            ? Storage::disk('public')->url($fotoPath)
            : null;
    }
}
