<?php

namespace App\Http\Controllers;

use App\Models\Hafalan;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Santri;
use App\Models\Team;
use App\Models\TeamInvitation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, Team $current_team): Response
    {
        $user = $request->user();
        $email = strtolower($user->email);

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        $scope = $user->contentScope($current_team);
        $isAggregate = $scope['isAggregate'];
        $teamIds = $scope['teamIds'];

        $genderScope = $isAggregate ? null : $user->santriGenderFor($current_team);

        $postsQuery = Post::query()->whereIn('team_id', $teamIds);
        $santriQuery = Santri::query()
            ->whereIn('team_id', $teamIds)
            ->when($genderScope !== null, fn ($query) => $query->where('gender', $genderScope));

        $stats = [
            'posts' => (clone $postsQuery)->count(),
            'published_posts' => (clone $postsQuery)->published()->count(),
            'categories' => PostCategory::whereIn('team_id', $teamIds)->count(),
            'santri' => (clone $santriQuery)->count(),
            'active_santri' => (clone $santriQuery)->aktif()->count(),
        ];

        $hafalanQuery = Hafalan::query()
            ->whereIn('team_id', $teamIds)
            ->when($genderScope !== null, fn ($query) => $query->whereHas('santri', fn ($query) => $query->where('gender', $genderScope)));

        $stats['setoran'] = (clone $hafalanQuery)->count();
        $stats['setoran_bulan_ini'] = (clone $hafalanQuery)
            ->where('tanggal', '>=', now()->startOfMonth())
            ->count();

        $recentPosts = (clone $postsQuery)
            ->with(['category:id,name', 'team:id,name,slug'])
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Post $post) => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'status' => $post->status->value,
                'status_label' => $post->status->label(),
                'published_at' => $post->published_at?->toISOString(),
                'category' => $post->category
                    ? ['id' => $post->category->id, 'name' => $post->category->name]
                    : null,
                'team' => [
                    'id' => $post->team->id,
                    'name' => $post->team->name,
                    'slug' => $post->team->slug,
                ],
            ]);

        $recentSantri = (clone $santriQuery)
            ->with('team:id,name,slug')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Santri $santri) => [
                'id' => $santri->id,
                'nis' => $santri->nis,
                'name' => $santri->name,
                'status' => $santri->status->value,
                'status_label' => $santri->status->label(),
                'team' => [
                    'id' => $santri->team->id,
                    'name' => $santri->team->name,
                    'slug' => $santri->team->slug,
                ],
            ]);

        $recentSetorans = (clone $hafalanQuery)
            ->with(['santri:id,name,nis', 'kategori:id,nama', 'team:id,name,slug'])
            ->latest('tanggal')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Hafalan $hafalan) => [
                'id' => $hafalan->id,
                'santri' => [
                    'id' => $hafalan->santri->id,
                    'name' => $hafalan->santri->name,
                    'nis' => $hafalan->santri->nis,
                ],
                'type' => $hafalan->type->value,
                'type_label' => $hafalan->type->label(),
                'juz' => $hafalan->juz,
                'surat' => $hafalan->surat,
                'ayat_from' => $hafalan->ayat_from,
                'ayat_to' => $hafalan->ayat_to,
                'tanggal' => $hafalan->tanggal->format('Y-m-d'),
                'kategori' => $hafalan->kategori
                    ? ['id' => $hafalan->kategori->id, 'nama' => $hafalan->kategori->nama]
                    : null,
                'team' => [
                    'id' => $hafalan->team->id,
                    'name' => $hafalan->team->name,
                    'slug' => $hafalan->team->slug,
                ],
            ]);

        return Inertia::render('dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'stats' => $stats,
            'recentPosts' => $recentPosts,
            'recentSantri' => $recentSantri,
            'recentSetorans' => $recentSetorans,
            'isAggregate' => $isAggregate,
            'team' => [
                'id' => $current_team->id,
                'name' => $current_team->name,
                'slug' => $current_team->slug,
            ],
        ]);
    }
}
