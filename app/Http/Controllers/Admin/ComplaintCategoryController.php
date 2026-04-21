<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ComplaintCategoryRequest;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ComplaintCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ComplaintCategory::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'in:1,0'],
        ]);

        $categories = ComplaintCategory::query()
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
            ->through(fn (ComplaintCategory $category): array => [
                'id' => $category->id,
                'code' => $category->code,
                'name_en' => $category->name_en,
                'name_am' => $category->name_am,
                'description' => $category->description,
                'is_active' => $category->is_active,
                'can' => [
                    'update' => $request->user()?->can('update', $category) ?? false,
                    'delete' => $request->user()?->can('delete', $category) ?? false,
                ],
            ]);

        return Inertia::render('Admin/ComplaintCategories/Index', [
            'filters' => $filters,
            'categories' => $categories,
            'can' => [
                'create' => $request->user()?->can('create', ComplaintCategory::class) ?? false,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ComplaintCategory::class);

        return Inertia::render('Admin/ComplaintCategories/Form', [
            'categoryItem' => null,
            'canDelete' => false,
        ]);
    }

    public function show(ComplaintCategory $complaintCategory): Response
    {
        $this->authorize('view', $complaintCategory);

        return Inertia::render('Admin/ComplaintCategories/Show', [
            'categoryItem' => [
                'id' => $complaintCategory->id,
                'code' => $complaintCategory->code,
                'name_en' => $complaintCategory->name_en,
                'name_am' => $complaintCategory->name_am,
                'description' => $complaintCategory->description,
                'is_active' => $complaintCategory->is_active,
                'stats' => [
                    'complaints' => $this->complaintsUsingCategory($complaintCategory),
                ],
            ],
            'can' => [
                'update' => request()->user()?->can('update', $complaintCategory) ?? false,
                'delete' => request()->user()?->can('delete', $complaintCategory) ?? false,
            ],
        ]);
    }

    public function store(ComplaintCategoryRequest $request): RedirectResponse
    {
        $category = new ComplaintCategory;
        $category->fill($request->validated());
        $category->save();

        return to_route('complaint-categories.edit', $category)->with('success', __('Complaint category created successfully.'));
    }

    public function edit(ComplaintCategory $complaintCategory): Response
    {
        $this->authorize('update', $complaintCategory);

        return Inertia::render('Admin/ComplaintCategories/Form', [
            'categoryItem' => [
                'id' => $complaintCategory->id,
                'code' => $complaintCategory->code,
                'name_en' => $complaintCategory->name_en,
                'name_am' => $complaintCategory->name_am,
                'description' => $complaintCategory->description,
                'is_active' => $complaintCategory->is_active,
            ],
            'canDelete' => request()->user()?->can('delete', $complaintCategory) ?? false,
        ]);
    }

    public function update(ComplaintCategoryRequest $request, ComplaintCategory $complaintCategory): RedirectResponse
    {
        $complaintCategory->fill($request->validated());
        $complaintCategory->save();

        return to_route('complaint-categories.edit', $complaintCategory)->with('success', __('Complaint category updated successfully.'));
    }

    public function destroy(Request $request, ComplaintCategory $complaintCategory): RedirectResponse
    {
        $this->authorize('delete', $complaintCategory);

        if ($this->complaintsUsingCategory($complaintCategory) > 0) {
            return back()->with('error', __('This complaint category cannot be deleted while it is used by complaints.'));
        }

        $complaintCategory->delete();

        return to_route('complaint-categories.index')->with('success', __('Complaint category deleted successfully.'));
    }

    private function complaintsUsingCategory(ComplaintCategory $complaintCategory): int
    {
        $values = array_values(array_unique(array_filter([
            $complaintCategory->code,
            $complaintCategory->name_en,
            $complaintCategory->name_am,
        ])));

        if ($values === []) {
            return 0;
        }

        return Complaint::query()->whereIn('category', $values)->count();
    }
}
