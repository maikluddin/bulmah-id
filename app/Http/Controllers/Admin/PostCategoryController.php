<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePostCategoryRequest;
use App\Models\PostCategory;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PostCategoryController extends Controller
{
    /**
     * Display a listing of the team's post categories.
     */
    public function index(Team $current_team): Response
    {
        Gate::authorize('manageCategories', $current_team);

        $scope = request()->user()->contentScope($current_team);

        $categories = PostCategory::query()
            ->whereIn('team_id', $scope['teamIds'])
            ->withCount('posts')
            ->with('team:id,name,slug')
            ->orderBy('name')
            ->get()
            ->map(fn (PostCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'posts_count' => $category->posts_count,
                'team' => [
                    'id' => $category->team->id,
                    'name' => $category->team->name,
                    'slug' => $category->team->slug,
                ],
            ]);

        return Inertia::render('admin/categories/index', [
            'categories' => $categories,
            'isAggregate' => $scope['isAggregate'],
            'team' => [
                'id' => $current_team->id,
                'name' => $current_team->name,
                'slug' => $current_team->slug,
            ],
        ]);
    }

    /**
     * Store a newly created post category.
     */
    public function store(SavePostCategoryRequest $request, Team $current_team): RedirectResponse
    {
        Gate::authorize('manageCategories', $current_team);

        $current_team->postCategories()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Kategori berhasil dibuat.')]);

        return back();
    }

    /**
     * Update the specified post category.
     */
    public function update(SavePostCategoryRequest $request, Team $current_team, PostCategory $category): RedirectResponse
    {
        Gate::authorize('manageCategories', $current_team);
        abort_unless($category->team_id === $current_team->id, 404);

        $category->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Kategori berhasil diperbarui.')]);

        return back();
    }

    /**
     * Remove the specified post category.
     */
    public function destroy(Team $current_team, PostCategory $category): RedirectResponse
    {
        Gate::authorize('manageCategories', $current_team);
        abort_unless($category->team_id === $current_team->id, 404);

        if ($category->posts()->exists()) {
            throw ValidationException::withMessages([
                'category' => __('Kategori yang masih memiliki berita tidak dapat dihapus.'),
            ]);
        }

        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Kategori berhasil dihapus.')]);

        return back();
    }
}
