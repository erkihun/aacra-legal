<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidPrimaryKey;
use App\Enums\CaseStatus;
use App\Enums\DirectorDecision;
use App\Enums\LegalCaseMainType;
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

class LegalCase extends Model
{
    use HasFactory;
    use HasUuidPrimaryKey;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'case_number',
        'external_court_file_number',
        'court_id',
        'case_type_id',
        'main_case_type',
        'registered_by_id',
        'director_reviewer_id',
        'assigned_team_leader_id',
        'assigned_legal_expert_id',
        'plaintiff',
        'defendant',
        'bench_or_chamber',
        'status',
        'workflow_stage',
        'priority',
        'director_decision',
        'claim_summary',
        'institution_position',
        'amount',
        'crime_scene',
        'police_station',
        'stolen_property_type',
        'stolen_property_estimated_value',
        'suspect_names',
        'statement_date',
        'outcome',
        'director_notes',
        'filing_date',
        'next_hearing_date',
        'decision_date',
        'appeal_deadline',
        'completed_at',
        'reopened_at',
        'reopened_by_id',
        'reopen_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => CaseStatus::class,
            'workflow_stage' => WorkflowStage::class,
            'priority' => PriorityLevel::class,
            'director_decision' => DirectorDecision::class,
            'main_case_type' => LegalCaseMainType::class,
            'amount' => 'decimal:2',
            'stolen_property_estimated_value' => 'decimal:2',
            'filing_date' => 'date',
            'next_hearing_date' => 'date',
            'statement_date' => 'date',
            'decision_date' => 'date',
            'appeal_deadline' => 'date',
            'completed_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function caseType(): BelongsTo
    {
        return $this->belongsTo(CaseType::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_id');
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

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CaseAssignment::class);
    }

    public function hearings(): HasMany
    {
        return $this->hasMany(CaseHearing::class);
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
        return $query->where('status', CaseStatus::UNDER_DIRECTOR_REVIEW);
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->hasGlobalCaseVisibility()) {
            return $query;
        }

        if ($user->canLeadLitigationWorkflow()) {
            return $query->where('assigned_team_leader_id', $user->getKey());
        }

        if ($user->canHandleAssignedCases()) {
            return $query->where('assigned_legal_expert_id', $user->getKey());
        }

        return $query->where('registered_by_id', $user->getKey());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function isClosed(): bool
    {
        return $this->status === CaseStatus::CLOSED;
    }
}
