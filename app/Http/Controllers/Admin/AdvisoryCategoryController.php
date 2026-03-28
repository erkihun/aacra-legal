<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdvisoryCategoryRequest;
use App\Models\AdvisoryCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdvisoryCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AdvisoryCategory::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'in:1,0'],
        ]);

        $categories = AdvisoryCategory::query()
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%")
                        ->orWhere('name_am', 'like', "%{$search}%");
                });
            })
            ->when(
                array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '',
                fn ($query) => $query->where('is_active', $filters['is_active'] === '1'),
            )
            ->orderBy('name_en')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (AdvisoryCategory $category): array => [
                'id' => $category->id,
                'code' => $category->code,
                'name_en' => $category->name_en,
                'name_am' => $category->name_am,
                'description' => $category->description,
                'is_active' => $category->is_active,
            ]);

        return Inertia::render('Admin/AdvisoryCategories/Index', [
            'filters' => $filters,
            'categories' => $categories,
            'can' => [
                'create' => $request->user()?->can('create', AdvisoryCategory::class) ?? false,
                'update' => $request->user()?->can('advisory-categories.manage') ?? false,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', AdvisoryCategory::class);

        return Inertia::render('Admin/AdvisoryCategories/Form', [
            'categoryItem' => null,
            'canDelete' => false,
        ]);
    }

    public function show(AdvisoryCategory $advisoryCategory): Response
    {
        $this->authorize('view', $advisoryCategory);

        $advisoryCategory->loadCount(['advisoryRequests']);

        return Inertia::render('Admin/AdvisoryCategories/Show', [
            'categoryItem' => [
                'id' => $advisoryCategory->id,
                'code' => $advisoryCategory->code,
                'name_en' => $advisoryCategory->name_en,
                'name_am' => $advisoryCategory->name_am,
                'description' => $advisoryCategory->description,
                'is_active' => $advisoryCategory->is_active,
                'stats' => [
                    'advisory_requests' => $advisoryCategory->advisory_requests_count,
                ],
            ],
            'can' => [
                'update' => request()->user()?->can('update', $advisoryCategory) ?? false,
                'delete' => request()->user()?->can('delete', $advisoryCategory) ?? false,
            ],
        ]);
    }

    public function store(AdvisoryCategoryRequest $request): RedirectResponse
    {
        $category = new AdvisoryCategory;
        $category->fill($request->validated());
        $category->save();

        return to_route('advisory-categories.edit', $category)->with('success', __('Advisory category created successfully.'));
    }

    public function edit(AdvisoryCategory $advisoryCategory): Response
    {
        $this->authorize('update', $advisoryCategory);

        return Inertia::render('Admin/AdvisoryCategories/Form', [
            'categoryItem' => [
                'id' => $advisoryCategory->id,
                'code' => $advisoryCategory->code,
                'name_en' => $advisoryCategory->name_en,
                'name_am' => $advisoryCategory->name_am,
                'description' => $advisoryCategory->description,
                'is_active' => $advisoryCategory->is_active,
            ],
            'canDelete' => request()->user()?->can('delete', $advisoryCategory) ?? false,
        ]);
    }

    public function update(AdvisoryCategoryRequest $request, AdvisoryCategory $advisoryCategory): RedirectResponse
    {
        $advisoryCategory->fill($request->validated());
        $advisoryCategory->save();

        return to_route('advisory-categories.edit', $advisoryCategory)->with('success', __('Advisory category updated successfully.'));
    }

    public function destroy(Request $request, AdvisoryCategory $advisoryCategory): RedirectResponse
    {
        $this->authorize('delete', $advisoryCategory);

        if ($advisoryCategory->advisoryRequests()->exists()) {
            return back()->with('error', __('This advisory category cannot be deleted while it is used by advisory requests.'));
        }

        $advisoryCategory->delete();

        return to_route('advisory-categories.index')->with('success', __('Advisory category deleted successfully.'));
    }
}
