<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DepartmentRequest;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Department::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'in:1,0'],
        ]);

        $departments = Department::query()
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
            ->through(fn (Department $department): array => [
                'id' => $department->id,
                'code' => $department->code,
                'name_en' => $department->name_en,
                'name_am' => $department->name_am,
                'is_active' => $department->is_active,
            ]);

        return Inertia::render('Admin/Departments/Index', [
            'filters' => $filters,
            'departments' => $departments,
            'can' => [
                'create' => $request->user()?->can('create', Department::class) ?? false,
                'update' => $request->user()?->can('departments.manage') ?? false,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Department::class);

        return Inertia::render('Admin/Departments/Form', [
            'departmentItem' => null,
            'canDelete' => false,
        ]);
    }

    public function show(Department $department): Response
    {
        $this->authorize('view', $department);

        $department->loadCount(['users', 'advisoryRequests']);

        return Inertia::render('Admin/Departments/Show', [
            'departmentItem' => [
                'id' => $department->id,
                'code' => $department->code,
                'name_en' => $department->name_en,
                'name_am' => $department->name_am,
                'is_active' => $department->is_active,
                'stats' => [
                    'users' => $department->users_count,
                    'advisory_requests' => $department->advisory_requests_count,
                ],
            ],
            'can' => [
                'update' => request()->user()?->can('update', $department) ?? false,
                'delete' => request()->user()?->can('delete', $department) ?? false,
            ],
        ]);
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        $department = new Department;
        $department->fill($request->validated());
        $department->save();

        return to_route('departments.edit', $department)->with('success', __('Department created successfully.'));
    }

    public function edit(Department $department): Response
    {
        $this->authorize('update', $department);

        return Inertia::render('Admin/Departments/Form', [
            'departmentItem' => [
                'id' => $department->id,
                'code' => $department->code,
                'name_en' => $department->name_en,
                'name_am' => $department->name_am,
                'is_active' => $department->is_active,
            ],
            'canDelete' => request()->user()?->can('delete', $department) ?? false,
        ]);
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->fill($request->validated());
        $department->save();

        return to_route('departments.edit', $department)->with('success', __('Department updated successfully.'));
    }

    public function destroy(Request $request, Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        if ($department->users()->exists() || $department->advisoryRequests()->exists()) {
            return back()->with('error', __('This department cannot be deleted while it is still linked to users or advisory requests.'));
        }

        $department->delete();

        return to_route('departments.index')->with('success', __('Department deleted successfully.'));
    }
}
