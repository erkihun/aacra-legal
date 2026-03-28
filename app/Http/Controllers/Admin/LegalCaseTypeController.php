<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LegalCaseTypeRequest;
use App\Models\CaseType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LegalCaseTypeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CaseType::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'in:1,0'],
        ]);

        $caseTypes = CaseType::query()
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
            ->through(fn (CaseType $caseType): array => [
                'id' => $caseType->id,
                'code' => $caseType->code,
                'name_en' => $caseType->name_en,
                'name_am' => $caseType->name_am,
                'description' => $caseType->description,
                'is_active' => $caseType->is_active,
            ]);

        return Inertia::render('Admin/LegalCaseTypes/Index', [
            'filters' => $filters,
            'caseTypes' => $caseTypes,
            'can' => [
                'create' => $request->user()?->can('create', CaseType::class) ?? false,
                'update' => $request->user()?->can('legal-case-types.manage') ?? false,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', CaseType::class);

        return Inertia::render('Admin/LegalCaseTypes/Form', [
            'caseTypeItem' => null,
            'canDelete' => false,
        ]);
    }

    public function show(CaseType $caseType): Response
    {
        $this->authorize('view', $caseType);

        $caseType->loadCount(['legalCases']);

        return Inertia::render('Admin/LegalCaseTypes/Show', [
            'caseTypeItem' => [
                'id' => $caseType->id,
                'code' => $caseType->code,
                'name_en' => $caseType->name_en,
                'name_am' => $caseType->name_am,
                'description' => $caseType->description,
                'is_active' => $caseType->is_active,
                'stats' => [
                    'legal_cases' => $caseType->legal_cases_count,
                ],
            ],
            'can' => [
                'update' => request()->user()?->can('update', $caseType) ?? false,
                'delete' => request()->user()?->can('delete', $caseType) ?? false,
            ],
        ]);
    }

    public function store(LegalCaseTypeRequest $request): RedirectResponse
    {
        $caseType = new CaseType;
        $caseType->fill($request->validated());
        $caseType->save();

        return to_route('legal-case-types.edit', $caseType)->with('success', __('Legal case type created successfully.'));
    }

    public function edit(CaseType $caseType): Response
    {
        $this->authorize('update', $caseType);

        return Inertia::render('Admin/LegalCaseTypes/Form', [
            'caseTypeItem' => [
                'id' => $caseType->id,
                'code' => $caseType->code,
                'name_en' => $caseType->name_en,
                'name_am' => $caseType->name_am,
                'description' => $caseType->description,
                'is_active' => $caseType->is_active,
            ],
            'canDelete' => request()->user()?->can('delete', $caseType) ?? false,
        ]);
    }

    public function update(LegalCaseTypeRequest $request, CaseType $caseType): RedirectResponse
    {
        $caseType->fill($request->validated());
        $caseType->save();

        return to_route('legal-case-types.edit', $caseType)->with('success', __('Legal case type updated successfully.'));
    }

    public function destroy(Request $request, CaseType $caseType): RedirectResponse
    {
        $this->authorize('delete', $caseType);

        if ($caseType->legalCases()->exists()) {
            return back()->with('error', __('This legal case type cannot be deleted while it is used by legal cases.'));
        }

        $caseType->delete();

        return to_route('legal-case-types.index')->with('success', __('Legal case type deleted successfully.'));
    }
}
