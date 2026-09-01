<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidPrimaryKey;
use App\Enums\AdvisoryRequestStatus;
use App\Enums\AdvisoryRequestType;
use App\Enums\DirectorDecision;
use App\Enums\PriorityLevel;
use App\Enums\WorkflowStage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AdvisoryRequest extends Model
{
    use HasFactory;
    use HasUuidPrimaryKey;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'request_number',
        'department_id',
        'category_id',
        'requester_user_id',
        'requester_account_id',
        'letter_template_id',
        'letter_snapshot',
        'director_reviewer_id',
        'assigned_team_leader_id',
        'assigned_legal_expert_id',
        'subject',
        'request_type',
        'status',
        'workflow_stage',
        'priority',
        'director_decision',
        'description',
        'director_notes',
        'internal_summary',
        'date_submitted',
        'due_date',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'request_type' => AdvisoryRequestType::class,
            'status' => AdvisoryRequestStatus::class,
            'workflow_stage' => WorkflowStage::class,
            'priority' => PriorityLevel::class,
            'director_decision' => DirectorDecision::class,
            'letter_snapshot' => 'array',
            'date_submitted' => 'date',
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AdvisoryCategory::class, 'category_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function requesterAccount(): BelongsTo
    {
        return $this->belongsTo(RequesterAccount::class, 'requester_account_id');
    }

    public function letterTemplate(): BelongsTo
    {
        return $this->belongsTo(LetterTemplate::class, 'letter_template_id');
    }

    public function directorReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_reviewer_id');
    }

    public function assignedTeamLeader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_team_leader_id');
    }

    public function assignedLegalExpert(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_legal_expert_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AdvisoryAssignment::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(AdvisoryResponse::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function scopePendingDirector($query)
    {
        return $query->where('status', AdvisoryRequestStatus::UNDER_DIRECTOR_REVIEW);
    }

    public function scopeAssignedTo($query, User $user)
    {
        return $query->where('assigned_legal_expert_id', $user->getKey());
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->hasGlobalAdvisoryVisibility()) {
            return $query;
        }

        if ($user->canLeadAdvisoryWorkflow()) {
            return $query->where(function ($builder) use ($user): void {
                $builder
                    ->whereHas('assignedTeamLeader', fn ($leaderQuery) => $leaderQuery->where('team_id', $user->team_id))
                    ->orWhere(function ($fallbackQuery) use ($user): void {
                        $fallbackQuery
                            ->whereNull('assigned_team_leader_id')
                            ->whereHas('assignedLegalExpert', fn ($expertQuery) => $expertQuery->where('team_id', $user->team_id));
                    });
            });
        }

        if (
            $user->canRespondToAdvisories()
            || $user->can('advisory.respond')
            || $user->can('advisory-requests.respond')
        ) {
            return $query->where('assigned_legal_expert_id', $user->getKey());
        }

        return $query->where('requester_user_id', $user->getKey());
    }

    public function assignedTeamId(): ?string
    {
        return $this->assignedTeamLeader?->team_id ?? $this->assignedLegalExpert?->team_id;
    }

    public function isVisibleToTeamLeader(User $user): bool
    {
        return $user->canLeadAdvisoryWorkflow()
            && $user->team_id !== null
            && $this->assignedTeamId() === $user->team_id;
    }

    public function isVisibleToAssignedExpert(User $user): bool
    {
        return (
            $user->canRespondToAdvisories()
            || $user->can('advisory.respond')
            || $user->can('advisory-requests.respond')
        )
            && $this->assigned_legal_expert_id === $user->getKey();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'department_id',
                'category_id',
                'requester_user_id',
                'director_reviewer_id',
                'assigned_team_leader_id',
                'assigned_legal_expert_id',
                'subject',
                'request_type',
                'status',
                'workflow_stage',
                'priority',
                'director_decision',
                'due_date',
                'completed_at',
            ])
            ->logOnlyDirty();
    }
}
