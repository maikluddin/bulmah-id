<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Wali\CreateWaliAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveWaliRequest;
use App\Models\Santri;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WaliController extends Controller
{
    /**
     * Display a listing of the team's wali (guardian) accounts.
     */
    public function index(Request $request, Team $current_team): Response
    {
        Gate::authorize('manageWali', $current_team);

        $scope = $request->user()->contentScope($current_team);
        $search = $request->string('search')->trim()->toString();

        $walis = User::query()
            ->whereHas('santris', fn ($query) => $query->whereIn('team_id', $scope['teamIds']))
            ->withCount(['santris as santris_count' => fn ($query) => $query->whereIn('team_id', $scope['teamIds'])])
            ->with(['santris' => fn ($query) => $query
                ->whereIn('team_id', $scope['teamIds'])
                ->select('id', 'team_id', 'wali_user_id', 'wali_phone', 'name')])
            ->when($search !== '', fn ($query) => $query
                ->where(fn ($query) => $query
                    ->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")))
            ->orderBy('users.name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->format('Y-m-d H:i:s'),
                'phone' => $user->santris->first()?->wali_phone,
                'santris_count' => $user->santris_count,
                'santri_names' => $user->santris->take(3)->pluck('name')->all(),
                'team' => $this->toTeamData($current_team),
            ]);

        return Inertia::render('admin/wali/index', [
            'walis' => $walis,
            'filters' => compact('search'),
            'team' => $this->toTeamData($current_team),
        ]);
    }

    /**
     * Show the form for creating a new wali account.
     */
    public function create(Request $request, Team $current_team): Response
    {
        Gate::authorize('manageWali', $current_team);

        return Inertia::render('admin/wali/form', [
            'wali' => null,
            'santriOptions' => $this->santriOptions($request, $current_team),
            'linkedSantriIds' => [],
            'team' => $this->toTeamData($current_team),
        ]);
    }

    /**
     * Store a newly created wali account.
     */
    public function store(SaveWaliRequest $request, Team $current_team, CreateWaliAccount $createWaliAccount): RedirectResponse
    {
        Gate::authorize('manageWali', $current_team);

        $user = $createWaliAccount->handle($current_team, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Akun wali berhasil dibuat.')]);

        return to_route('admin.wali.edit', ['current_team' => $current_team->slug, 'user' => $user->id]);
    }

    /**
     * Show the form for editing the specified wali account.
     */
    public function edit(Request $request, Team $current_team, User $user): Response
    {
        Gate::authorize('manageWali', $current_team);
        $this->ensureWaliInScope($request, $current_team, $user);

        $linkedIds = $this->linkedSantriIds($request, $current_team, $user);
        $phone = $this->linkedSantris($request, $current_team, $user)->first()?->wali_phone;

        return Inertia::render('admin/wali/form', [
            'wali' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $phone,
                'santris_count' => count($linkedIds),
            ],
            'santriOptions' => $this->santriOptions($request, $current_team, $linkedIds),
            'linkedSantriIds' => $linkedIds,
            'team' => $this->toTeamData($current_team),
        ]);
    }

    /**
     * Update the specified wali account.
     */
    public function update(SaveWaliRequest $request, Team $current_team, User $user): RedirectResponse
    {
        Gate::authorize('manageWali', $current_team);
        $this->ensureWaliInScope($request, $current_team, $user);

        $data = $request->validated();

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if (filled($data['password'] ?? null)) {
            $user->update(['password' => $data['password']]);
        }

        $this->syncSantriLinks($request, $current_team, $user, $data['santri_ids'] ?? [], $data['phone'] ?? null);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Akun wali berhasil diperbarui.')]);

        return to_route('admin.wali.edit', ['current_team' => $current_team->slug, 'user' => $user->id]);
    }

    /**
     * Remove the specified wali account from the team.
     */
    public function destroy(Request $request, Team $current_team, User $user): RedirectResponse
    {
        Gate::authorize('manageWali', $current_team);
        $this->ensureWaliInScope($request, $current_team, $user);

        $scope = $request->user()->contentScope($current_team);

        Santri::query()
            ->whereIn('team_id', $scope['teamIds'])
            ->where('wali_user_id', $user->id)
            ->update(['wali_user_id' => null]);

        $current_team->memberships()->where('user_id', $user->id)->delete();

        if (! $user->teams()->where('teams.is_personal', false)->exists()) {
            $user->delete();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Akun wali berhasil dihapus.')]);

        return to_route('admin.wali.index', ['current_team' => $current_team->slug]);
    }

    /**
     * Abort the request when the wali does not supervise any santri in scope.
     */
    protected function ensureWaliInScope(Request $request, Team $team, User $user): void
    {
        $scope = $request->user()->contentScope($team);

        abort_unless(
            Santri::query()
                ->where('wali_user_id', $user->id)
                ->whereIn('team_id', $scope['teamIds'])
                ->exists(),
            404,
        );
    }

    /**
     * Get the linked santri options for the wali form, limited to the user's
     * content and gender scope.
     *
     * @param  array<int, int>  $linkedIds
     * @return array<int, array<string, mixed>>
     */
    protected function santriOptions(Request $request, Team $team, array $linkedIds = []): array
    {
        $scope = $request->user()->contentScope($team);
        $genderScope = $scope['isAggregate'] ? null : $request->user()->santriGenderFor($team);

        return Santri::query()
            ->whereIn('team_id', $scope['teamIds'])
            ->when($genderScope !== null, fn ($query) => $query->where('gender', $genderScope))
            ->where(fn ($query) => $query
                ->whereNull('wali_user_id')
                ->when($linkedIds !== [], fn ($query) => $query->orWhereIn('id', $linkedIds)))
            ->with(['team:id,name,slug', 'kelas:id,nama'])
            ->orderBy('name')
            ->get()
            ->map(fn (Santri $santri) => [
                'id' => $santri->id,
                'nis' => $santri->nis,
                'name' => $santri->name,
                'gender_label' => $santri->gender->label(),
                'kelas' => $santri->kelas ? ['nama' => $santri->kelas->nama] : null,
                'team' => [
                    'name' => $santri->team->name,
                    'slug' => $santri->team->slug,
                ],
                'linked' => in_array($santri->id, $linkedIds),
            ])
            ->all();
    }

    /**
     * Get the IDs of the santri linked to the wali within the user's scope.
     *
     * @return array<int, int>
     */
    protected function linkedSantriIds(Request $request, Team $team, User $user): array
    {
        return $this->linkedSantris($request, $team, $user)->pluck('id')->all();
    }

    /**
     * Get the santri linked to the wali within the user's scope.
     *
     * @return Collection<int, Santri>
     */
    protected function linkedSantris(Request $request, Team $team, User $user): Collection
    {
        return Santri::query()
            ->where('wali_user_id', $user->id)
            ->whereIn('team_id', $request->user()->contentScope($team)['teamIds'])
            ->get(['id', 'team_id', 'wali_user_id', 'wali_phone']);
    }

    /**
     * Sync the santri links for the given wali account and keep the
     * denormalized wali contact fields on each santri in sync.
     *
     * @param  array<int, int>  $santriIds
     */
    protected function syncSantriLinks(Request $request, Team $team, User $user, array $santriIds, ?string $phone): void
    {
        $scope = $request->user()->contentScope($team);
        $previous = $this->linkedSantriIds($request, $team, $user);

        $toLink = array_diff($santriIds, $previous);
        $toUnlink = array_diff($previous, $santriIds);

        Santri::query()
            ->whereIn('team_id', $scope['teamIds'])
            ->whereIn('id', $toUnlink)
            ->update(['wali_user_id' => null]);

        Santri::query()
            ->whereIn('team_id', $scope['teamIds'])
            ->whereIn('id', $toLink)
            ->update([
                'wali_user_id' => $user->id,
                'wali_name' => $user->name,
                'wali_phone' => $phone,
            ]);

        Santri::query()
            ->where('wali_user_id', $user->id)
            ->whereIn('team_id', $scope['teamIds'])
            ->update([
                'wali_name' => $user->name,
                'wali_phone' => $phone,
            ]);
    }

    /**
     * @return array{id: int, name: string, slug: string}
     */
    protected function toTeamData(Team $team): array
    {
        return [
            'id' => $team->id,
            'name' => $team->name,
            'slug' => $team->slug,
        ];
    }
}
