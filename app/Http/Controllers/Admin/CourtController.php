<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourtRequest;
use App\Models\Court;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourtController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Court::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'in:1,0'],
        ]);

        $courts = Court::query()
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%")
                        ->orWhere('name_am', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('level', 'like', "%{$search}%");
                });
            })
            ->when(
                array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '',
                fn ($query) => $query->where('is_active', $filters['is_active'] === '1'),
            )
            ->orderBy('name_en')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Court $court): array => [
                'id' => $court->id,
                'code' => $court->code,
                'name_en' => $court->name_en,
                'name_am' => $court->name_am,
                'level' => $court->level,
                'city' => $court->city,
                'is_active' => $court->is_active,
            ]);

        return Inertia::render('Admin/Courts/Index', [
            'filters' => $filters,
            'courts' => $courts,
            'can' => [
                'create' => $request->user()?->can('create', Court::class) ?? false,
                'update' => $request->user()?->can('courts.manage') ?? false,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Court::class);

        return Inertia::render('Admin/Courts/Form', [
            'courtItem' => null,
            'canDelete' => false,
        ]);
    }

    public function show(Court $court): Response
    {
        $this->authorize('view', $court);

        $court->loadCount(['legalCases']);

        return Inertia::render('Admin/Courts/Show', [
            'courtItem' => [
                'id' => $court->id,
                'code' => $court->code,
                'name_en' => $court->name_en,
                'name_am' => $court->name_am,
                'level' => $court->level,
                'city' => $court->city,
                'is_active' => $court->is_active,
                'stats' => [
                    'legal_cases' => $court->legal_cases_count,
                ],
            ],
            'can' => [
                'update' => request()->user()?->can('update', $court) ?? false,
                'delete' => request()->user()?->can('delete', $court) ?? false,
            ],
        ]);
    }

    public function store(CourtRequest $request): RedirectResponse
    {
        $court = new Court;
        $court->fill($request->validated());
        $court->save();

        return to_route('courts.edit', $court)->with('success', __('Court created successfully.'));
    }

    public function edit(Court $court): Response
    {
        $this->authorize('update', $court);

        return Inertia::render('Admin/Courts/Form', [
            'courtItem' => [
                'id' => $court->id,
                'code' => $court->code,
                'name_en' => $court->name_en,
                'name_am' => $court->name_am,
                'level' => $court->level,
                'city' => $court->city,
                'is_active' => $court->is_active,
            ],
            'canDelete' => request()->user()?->can('delete', $court) ?? false,
        ]);
    }

    public function update(CourtRequest $request, Court $court): RedirectResponse
    {
        $court->fill($request->validated());
        $court->save();

        return to_route('courts.edit', $court)->with('success', __('Court updated successfully.'));
    }

    public function destroy(Request $request, Court $court): RedirectResponse
    {
        $this->authorize('delete', $court);

        if ($court->legalCases()->exists()) {
            return back()->with('error', __('This court cannot be deleted while it is used by legal cases.'));
        }

        $court->delete();

        return to_route('courts.index')->with('success', __('Court deleted successfully.'));
    }
}
