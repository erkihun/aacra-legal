<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Models\Activity;

class BuildAuditTimelineDataAction
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function execute(User $user, array $filters): array
    {
        $query = Activity::query()
            ->with('causer')
            ->latest('created_at')
            ->when($filters['actor_id'] ?? null, fn ($builder, string $actorId) => $builder->where('causer_id', $actorId))
            ->when($filters['event'] ?? null, fn ($builder, string $event) => $builder->where('event', $event))
            ->when($filters['subject_type'] ?? null, fn ($builder, string $subjectType) => $builder->where('subject_type', $subjectType))
            ->when($filters['search'] ?? null, function ($builder, string $search): void {
                $builder->where(function ($nested) use ($search): void {
                    $nested
                        ->where('description', 'like', "%{$search}%")
                        ->orWhere('subject_type', 'like', "%{$search}%")
                        ->orWhere('event', 'like', "%{$search}%");
                });
            });

        return [
            'filters' => $filters,
            'items' => $this->paginate($query->paginate(20)->withQueryString()),
            'actorOptions' => User::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $actor) => [
                    'value' => $actor->id,
                    'label' => $actor->name,
                ]),
            'subjectTypeOptions' => Activity::query()
                ->select('subject_type')
                ->whereNotNull('subject_type')
                ->distinct()
                ->orderBy('subject_type')
                ->pluck('subject_type')
                ->map(fn (string $subjectType) => [
                    'value' => $subjectType,
                    'label' => $this->subjectTypeLabel($subjectType),
                ])
                ->values(),
            'eventOptions' => Activity::query()
                ->select('event')
                ->whereNotNull('event')
                ->distinct()
                ->orderBy('event')
                ->pluck('event')
                ->map(fn (string $event) => [
                    'value' => $event,
                    'label' => __("audit.events.{$event}"),
                ])
                ->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paginate(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => $paginator->getCollection()->map(fn (Activity $activity) => [
                'id' => (string) $activity->id,
                'log_name' => $activity->log_name,
                'description' => $this->localizedDescription($activity),
                'event' => $activity->event,
                'subject_type' => $activity->subject_type,
                'subject_id' => $activity->subject_id,
                'causer' => $activity->causer?->name,
                'changes_summary' => $this->changesSummary($activity),
                'created_at' => $activity->created_at?->toIso8601String(),
            ])->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'links' => $paginator->linkCollection()->toArray(),
        ];
    }

    private function changesSummary(Activity $activity): ?string
    {
        $attributes = Arr::wrap($activity->properties['attributes'] ?? []);
        $old = Arr::wrap($activity->properties['old'] ?? []);
        $changedKeys = collect(array_unique([
            ...array_keys($attributes),
            ...array_keys($old),
        ]))
            ->reject(fn (string $key) => in_array($key, ['updated_at', 'created_at'], true))
            ->values();

        if ($changedKeys->isEmpty()) {
            return null;
        }

        return $changedKeys
            ->take(4)
            ->map(function (string $key): string {
                $label = __("audit.fields.{$key}");

                return $label === "audit.fields.{$key}"
                    ? str($key)->replace('_', ' ')->headline()->toString()
                    : $label;
            })
            ->implode(', ');
    }

    private function localizedDescription(Activity $activity): string
    {
        if (
            $activity->description === 'Settings group updated.'
            && is_string($activity->properties['group'] ?? null)
        ) {
            $group = (string) $activity->properties['group'];

            return __('audit.descriptions.settings_group_updated', [
                'group' => __("settings.groups.{$group}"),
            ]);
        }

        return __($activity->description);
    }

    private function subjectTypeLabel(string $subjectType): string
    {
        $baseName = class_basename($subjectType);
        $translation = __("audit.subject_types.{$baseName}");

        return $translation === "audit.subject_types.{$baseName}"
            ? $baseName
            : $translation;
    }
}
