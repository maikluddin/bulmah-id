<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SantriGender;
use App\Enums\SantriStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSantriRequest;
use App\Models\Santri;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SantriController extends Controller
{
    /**
     * Display a listing of the team's santri.
     */
    public function index(Request $request, Team $current_team): Response
    {
        Gate::authorize('manageSantri', $current_team);

        $scope = $request->user()->contentScope($current_team);
        $genderScope = $scope['isAggregate'] ? null : $request->user()->santriGenderFor($current_team);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $santris = Santri::query()
            ->whereIn('team_id', $scope['teamIds'])
            ->with(['team:id,name,slug', 'kelas:id,nama'])
            ->when($genderScope !== null, fn ($query) => $query->where('gender', $genderScope))
            ->when($search !== '', fn ($query) => $query
                ->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Santri $santri) => [
                'id' => $santri->id,
                'nis' => $santri->nis,
                'nisn' => $santri->nisn,
                'name' => $santri->name,
                'gender' => $santri->gender->value,
                'gender_label' => $santri->gender->label(),
                'birth_place' => $santri->birth_place,
                'birth_date' => $santri->birth_date?->format('Y-m-d'),
                'address' => $santri->address,
                'status' => $santri->status->value,
                'status_label' => $santri->status->label(),
                'kelas_id' => $santri->kelas_id,
                'kelas' => $santri->kelas ? [
                    'id' => $santri->kelas->id,
                    'nama' => $santri->kelas->nama,
                ] : null,
                'wali_name' => $santri->wali_name,
                'wali_phone' => $santri->wali_phone,
                'entry_date' => $santri->entry_date?->format('Y-m-d'),
                'exit_date' => $santri->exit_date?->format('Y-m-d'),
                'photo_path' => $santri->photo_path,
                'photo_url' => $this->photoUrl($santri->photo_path),
                'team' => [
                    'id' => $santri->team->id,
                    'name' => $santri->team->name,
                    'slug' => $santri->team->slug,
                ],
            ]);

        return Inertia::render('admin/santri/index', [
            'santris' => $santris,
            'statuses' => $this->toEnumOptions(SantriStatus::cases()),
            'filters' => compact('search', 'status'),
            'santriScope' => $genderScope?->value,
            'isAggregate' => $scope['isAggregate'],
            'team' => [
                'id' => $current_team->id,
                'name' => $current_team->name,
                'slug' => $current_team->slug,
            ],
        ]);
    }

    /**
     * Show the form for creating a new santri.
     */
    public function create(Team $current_team): Response
    {
        Gate::authorize('manageSantri', $current_team);

        $genderScope = request()->user()->santriGenderFor($current_team);

        return Inertia::render('admin/santri/form', [
            'santri' => null,
            'genders' => $this->genderOptions($genderScope),
            'statuses' => $this->toEnumOptions(SantriStatus::cases()),
            'santriScope' => $genderScope?->value,
            'kelas' => $this->toKelasOptions($current_team),
            'team' => [
                'id' => $current_team->id,
                'name' => $current_team->name,
                'slug' => $current_team->slug,
            ],
        ]);
    }

    /**
     * Store a newly created santri.
     */
    public function store(SaveSantriRequest $request, Team $current_team): RedirectResponse
    {
        Gate::authorize('manageSantri', $current_team);

        $genderScope = $request->user()->santriGenderFor($current_team);

        $data = $this->applyPhoto($request, null, $request->validated());
        $data['gender'] = $genderScope ?? $data['gender'];
        $data['nis'] = $data['nis'] !== null && $data['nis'] !== ''
            ? $data['nis']
            : Santri::nextNis($current_team);

        $santri = $current_team->santris()->create($data);
        $santri->wallet()->firstOrCreate();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Santri berhasil ditambahkan.')]);

        return to_route('admin.santri.edit', ['current_team' => $current_team->slug, 'santri' => $santri->id]);
    }

    /**
     * Show the form for editing the specified santri.
     */
    public function edit(Team $current_team, Santri $santri): Response
    {
        Gate::authorize('manageSantri', $current_team);
        $this->ensureSantriInScope($current_team, $santri);

        $genderScope = request()->user()->santriGenderFor($current_team);

        $santri->load('kelas');

        return Inertia::render('admin/santri/form', [
            'santri' => [
                'id' => $santri->id,
                'nis' => $santri->nis,
                'nisn' => $santri->nisn,
                'name' => $santri->name,
                'gender' => $santri->gender->value,
                'birth_place' => $santri->birth_place,
                'birth_date' => $santri->birth_date?->format('Y-m-d'),
                'address' => $santri->address,
                'status' => $santri->status->value,
                'kelas_id' => $santri->kelas_id,
                'kelas' => $santri->kelas ? [
                    'id' => $santri->kelas->id,
                    'nama' => $santri->kelas->nama,
                ] : null,
                'wali_name' => $santri->wali_name,
                'wali_phone' => $santri->wali_phone,
                'entry_date' => $santri->entry_date?->format('Y-m-d'),
                'exit_date' => $santri->exit_date?->format('Y-m-d'),
                'photo_path' => $santri->photo_path,
                'photo_url' => $this->photoUrl($santri->photo_path),
                'team' => [
                    'id' => $current_team->id,
                    'name' => $current_team->name,
                    'slug' => $current_team->slug,
                ],
            ],
            'genders' => $this->genderOptions($genderScope),
            'statuses' => $this->toEnumOptions(SantriStatus::cases()),
            'santriScope' => $genderScope?->value,
            'kelas' => $this->toKelasOptions($current_team),
            'team' => [
                'id' => $current_team->id,
                'name' => $current_team->name,
                'slug' => $current_team->slug,
            ],
        ]);
    }

    /**
     * Update the specified santri.
     */
    public function update(SaveSantriRequest $request, Team $current_team, Santri $santri): RedirectResponse
    {
        Gate::authorize('manageSantri', $current_team);
        $this->ensureSantriInScope($current_team, $santri);

        $genderScope = $request->user()->santriGenderFor($current_team);

        $data = $this->applyPhoto($request, $santri, $request->validated());
        $data['gender'] = $genderScope ?? $data['gender'];

        $santri->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Santri berhasil diperbarui.')]);

        return to_route('admin.santri.edit', ['current_team' => $current_team->slug, 'santri' => $santri->id]);
    }

    /**
     * Remove the specified santri.
     */
    public function destroy(Team $current_team, Santri $santri): RedirectResponse
    {
        Gate::authorize('manageSantri', $current_team);
        $this->ensureSantriInScope($current_team, $santri);

        $this->deletePhoto($santri->photo_path);

        $santri->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Santri berhasil dihapus.')]);

        return to_route('admin.santri.index', ['current_team' => $current_team->slug]);
    }

    /**
     * Abort the request when the santri is outside the user's gender scope.
     */
    protected function ensureSantriInScope(Team $current_team, Santri $santri): void
    {
        $genderScope = request()->user()->santriGenderFor($current_team);

        abort_unless(
            $santri->team_id === $current_team->id
                && ($genderScope === null || $santri->gender === $genderScope),
            404,
        );
    }

    /**
     * Merge photo upload handling into the validated payload.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function applyPhoto(SaveSantriRequest $request, ?Santri $santri, array $data): array
    {
        unset($data['photo'], $data['remove_photo']);

        if ($request->boolean('remove_photo') && $santri?->photo_path) {
            $this->deletePhoto($santri->photo_path);
            $data['photo_path'] = null;
        }

        if ($request->hasFile('photo')) {
            if ($santri?->photo_path) {
                $this->deletePhoto($santri->photo_path);
            }

            $data['photo_path'] = $request->file('photo')->store('santri', 'public');
        }

        return $data;
    }

    /**
     * Delete a stored santri photo, if any.
     */
    protected function deletePhoto(?string $photoPath): void
    {
        if ($photoPath) {
            Storage::disk('public')->delete($photoPath);
        }
    }

    /**
     * Get the public URL for a stored photo, if any.
     */
    protected function photoUrl(?string $photoPath): ?string
    {
        return $photoPath
            ? Storage::disk('public')->url($photoPath)
            : null;
    }

    /**
     * Get the gender options, limited to the user's scope when present.
     *
     * @return array<int, array{value: string, label: string}>
     */
    protected function genderOptions(?SantriGender $scope): array
    {
        return $scope !== null
            ? $this->toEnumOptions([$scope])
            : $this->toEnumOptions(SantriGender::cases());
    }

    /**
     * Get the kelas options for the current team.
     *
     * @return array<int, array{value: string, label: string}>
     */
    protected function toKelasOptions(Team $team): array
    {
        return $team->kelas()
            ->orderBy('nama')
            ->get(['id', 'nama'])
            ->map(fn ($kelas) => [
                'value' => (string) $kelas->id,
                'label' => $kelas->nama,
            ])
            ->all();
    }

    /**
     * @param  array<int, \BackedEnum>  $cases
     * @return array<int, array{value: string, label: string}>
     */
    protected function toEnumOptions(array $cases): array
    {
        return array_map(
            fn ($case) => ['value' => $case->value, 'label' => $case->label()],
            $cases,
        );
    }
}
