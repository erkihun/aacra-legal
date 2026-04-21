<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BranchRequest;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Branch::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'in:1,0'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $branches = Branch::query()
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%")
                        ->orWhere('name_am', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['location'] ?? null, function ($query, string $location): void {
                $query->where(function ($builder) use ($location): void {
                    $builder
                        ->where('region', 'like', "%{$location}%")
                        ->orWhere('city', 'like', "%{$location}%")
                        ->orWhere('address', 'like', "%{$location}%");
                });
            })
            ->when(
                array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '',
                fn ($query) => $query->where('is_active', $filters['is_active'] === '1'),
            )
            ->orderBy('name_en')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Branch $branch): array => [
                'id' => $branch->id,
                'code' => $branch->code,
                'name_en' => $branch->name_en,
                'name_am' => $branch->name_am,
                'region' => $branch->region,
                'city' => $branch->city,
                'phone' => $branch->phone,
                'email' => $branch->email,
                'is_active' => $branch->is_active,
                'can' => [
                    'update' => $request->user()?->can('update', $branch) ?? false,
                    'delete' => $request->user()?->can('delete', $branch) ?? false,
                ],
            ]);

        return Inertia::render('Admin/Branches/Index', [
            'filters' => $filters,
            'branches' => $branches,
            'can' => [
                'create' => $request->user()?->can('create', Branch::class) ?? false,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Branch::class);

        return Inertia::render('Admin/Branches/Form', [
            'branchItem' => null,
            'canDelete' => false,
        ]);
    }

    public function store(BranchRequest $request): RedirectResponse
    {
        $branch = Branch::query()->create($request->validated());

        return to_route('branches.edit', $branch)->with('success', __('Branch created successfully.'));
    }

    public function show(Branch $branch): Response
    {
        $this->authorize('view', $branch);

        $branch->loadCount(['users', 'complaints']);

        return Inertia::render('Admin/Branches/Show', [
            'branchItem' => [
                'id' => $branch->id,
                'code' => $branch->code,
                'name_en' => $branch->name_en,
                'name_am' => $branch->name_am,
                'region' => $branch->region,
                'city' => $branch->city,
                'address' => $branch->address,
                'phone' => $branch->phone,
                'email' => $branch->email,
                'manager_name' => $branch->manager_name,
                'notes' => $branch->notes,
                'is_head_office' => $branch->is_head_office,
                'is_active' => $branch->is_active,
                'created_at' => $branch->created_at?->toIso8601String(),
                'updated_at' => $branch->updated_at?->toIso8601String(),
                'stats' => [
                    'users' => $branch->users_count,
                    'complaints' => $branch->complaints_count,
                ],
            ],
            'can' => [
                'update' => request()->user()?->can('update', $branch) ?? false,
                'delete' => request()->user()?->can('delete', $branch) ?? false,
            ],
        ]);
    }

    public function edit(Branch $branch): Response
    {
        $this->authorize('update', $branch);

        return Inertia::render('Admin/Branches/Form', [
            'branchItem' => [
                'id' => $branch->id,
                'code' => $branch->code,
                'name_en' => $branch->name_en,
                'name_am' => $branch->name_am,
                'region' => $branch->region,
                'city' => $branch->city,
                'address' => $branch->address,
                'phone' => $branch->phone,
                'email' => $branch->email,
                'manager_name' => $branch->manager_name,
                'notes' => $branch->notes,
                'is_head_office' => $branch->is_head_office,
                'is_active' => $branch->is_active,
            ],
            'canDelete' => request()->user()?->can('delete', $branch) ?? false,
        ]);
    }

    public function update(BranchRequest $request, Branch $branch): RedirectResponse
    {
        $branch->fill($request->validated());
        $branch->save();

        return to_route('branches.edit', $branch)->with('success', __('Branch updated successfully.'));
    }

    public function destroy(Request $request, Branch $branch): RedirectResponse
    {
        $this->authorize('delete', $branch);

        if ($branch->users()->exists() || $branch->complaints()->exists()) {
            return back()->with('error', __('This branch cannot be deleted while it is still linked to users or complaints.'));
        }

        $branch->delete();

        return to_route('branches.index')->with('success', __('Branch deleted successfully.'));
    }
}
