<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePostRequest;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Team;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    /**
     * Display a listing of the team's posts.
     */
    public function index(Request $request, Team $current_team): Response
    {
        Gate::authorize('managePosts', $current_team);

        $scope = $request->user()->contentScope($current_team);

        $search = $request->string('search')->trim()->toString();
        $category = (int) $request->input('category');
        $status = $request->string('status')->trim()->toString();

        $posts = Post::query()
            ->whereIn('team_id', $scope['teamIds'])
            ->with(['category', 'author', 'team:id,name,slug'])
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->when($category > 0, fn ($query) => $query->where('category_id', $category))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Post $post) => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'status' => $post->status->value,
                'status_label' => $post->status->label(),
                'published_at' => $post->published_at?->toISOString(),
                'category' => $post->category ? ['id' => $post->category->id, 'name' => $post->category->name] : null,
                'author' => $post->author ? ['id' => $post->author->id, 'name' => $post->author->name] : null,
                'cover_path' => $post->cover_path,
                'cover_url' => $this->coverUrl($post->cover_path),
                'team' => $this->toTeamData($post->team),
            ]);

        return Inertia::render('admin/posts/index', [
            'posts' => $posts,
            'categories' => $this->toCategoryOptions($scope['teamIds']),
            'statuses' => $this->toEnumOptions(PostStatus::cases()),
            'filters' => compact('search', 'category', 'status'),
            'isAggregate' => $scope['isAggregate'],
            'team' => $this->toTeamData($current_team),
        ]);
    }

    /**
     * Show the form for creating a new post.
     */
    public function create(Team $current_team): Response
    {
        Gate::authorize('managePosts', $current_team);

        return Inertia::render('admin/posts/form', [
            'post' => null,
            'categories' => $this->toCategoryOptions([$current_team->id]),
            'statuses' => $this->toEnumOptions(PostStatus::cases()),
            'team' => $this->toTeamData($current_team),
        ]);
    }

    /**
     * Store a newly created post.
     */
    public function store(SavePostRequest $request, Team $current_team): RedirectResponse
    {
        Gate::authorize('managePosts', $current_team);

        $data = $this->applyCover($request, null, $request->validated());

        $post = $current_team->posts()->create([
            ...$data,
            'author_id' => $request->user()->id,
            'published_at' => $this->resolvePublishedAt($request),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Berita berhasil dibuat.')]);

        return to_route('admin.posts.edit', ['current_team' => $current_team->slug, 'post' => $post->slug]);
    }

    /**
     * Show the form for editing the specified post.
     */
    public function edit(Team $current_team, Post $post): Response
    {
        Gate::authorize('managePosts', $current_team);
        abort_unless($post->team_id === $current_team->id, 404);

        return Inertia::render('admin/posts/form', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'category_id' => $post->category_id,
                'excerpt' => $post->excerpt,
                'body' => $post->body,
                'status' => $post->status->value,
                'published_at' => $post->published_at?->format('Y-m-d'),
                'cover_path' => $post->cover_path,
                'cover_url' => $this->coverUrl($post->cover_path),
            ],
            'categories' => $this->toCategoryOptions([$current_team->id]),
            'statuses' => $this->toEnumOptions(PostStatus::cases()),
            'team' => $this->toTeamData($current_team),
        ]);
    }

    /**
     * Update the specified post.
     */
    public function update(SavePostRequest $request, Team $current_team, Post $post): RedirectResponse
    {
        Gate::authorize('managePosts', $current_team);
        abort_unless($post->team_id === $current_team->id, 404);

        $data = $this->applyCover($request, $post, $request->validated());

        $post->update([
            ...$data,
            'published_at' => $this->resolvePublishedAt($request),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Berita berhasil diperbarui.')]);

        return to_route('admin.posts.edit', ['current_team' => $current_team->slug, 'post' => $post->slug]);
    }

    /**
     * Remove the specified post.
     */
    public function destroy(Team $current_team, Post $post): RedirectResponse
    {
        Gate::authorize('managePosts', $current_team);
        abort_unless($post->team_id === $current_team->id, 404);

        $this->deleteCover($post->cover_path);

        $post->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Berita berhasil dihapus.')]);

        return to_route('admin.posts.index', ['current_team' => $current_team->slug]);
    }

    /**
     * Resolve the published_at value based on the selected status.
     */
    protected function resolvePublishedAt(SavePostRequest $request): ?CarbonInterface
    {
        $status = PostStatus::from($request->validated('status'));
        $publishedAt = $request->validated('published_at');

        if ($status === PostStatus::Published) {
            return blank($publishedAt) ? now() : Carbon::parse($publishedAt);
        }

        return null;
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    /**
     * Merge cover upload handling into the validated payload.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function applyCover(SavePostRequest $request, ?Post $post, array $data): array
    {
        unset($data['cover'], $data['remove_cover']);

        if ($request->boolean('remove_cover') && $post?->cover_path) {
            $this->deleteCover($post->cover_path);
            $data['cover_path'] = null;
        }

        if ($request->hasFile('cover')) {
            if ($post?->cover_path) {
                $this->deleteCover($post->cover_path);
            }

            $data['cover_path'] = $request->file('cover')->store('posts', 'public');
        }

        return $data;
    }

    /**
     * Delete a stored post cover, if any.
     */
    protected function deleteCover(?string $coverPath): void
    {
        if ($coverPath) {
            Storage::disk('public')->delete($coverPath);
        }
    }

    /**
     * Get the public URL for a stored cover, if any.
     */
    protected function coverUrl(?string $coverPath): ?string
    {
        return $coverPath
            ? Storage::disk('public')->url($coverPath)
            : null;
    }

    /**
     * @param  array<int, int>  $teamIds
     * @return array<int, array{id: int, name: string}>
     */
    protected function toCategoryOptions(array $teamIds): array
    {
        return PostCategory::query()
            ->whereIn('team_id', $teamIds)
            ->orderBy('name')
            ->get()
            ->map(fn (PostCategory $category) => ['id' => $category->id, 'name' => $category->name])
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
