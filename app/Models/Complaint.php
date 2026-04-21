<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidPrimaryKey;
use App\Enums\ComplaintComplainantType;
use App\Enums\ComplaintStatus;
use App\Enums\PriorityLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Complaint extends Model
{
    use HasFactory;
    use HasUuidPrimaryKey;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'complaint_number',
        'complainant_user_id',
        'branch_id',
        'department_id',
        'assigned_committee_user_id',
        'complainant_type',
        'complainant_name',
        'complainant_email',
        'complainant_phone',
        'subject',
        'details',
        'category',
        'priority',
        'submitted_at',
        'department_response_deadline_at',
        'department_responded_at',
        'forwarded_to_committee_at',
        'committee_review_started_at',
        'committee_decision_at',
        'resolved_at',
        'closed_at',
        'status',
        'is_overdue',
        'is_escalated',
        'is_auto_escalated',
        'is_dissatisfied',
        'dissatisfaction_reason',
    ];

    protected function casts(): array
    {
        return [
            'complainant_type' => ComplaintComplainantType::class,
            'priority' => PriorityLevel::class,
            'status' => ComplaintStatus::class,
            'submitted_at' => 'datetime',
            'department_response_deadline_at' => 'datetime',
            'department_responded_at' => 'datetime',
            'forwarded_to_committee_at' => 'datetime',
            'committee_review_started_at' => 'datetime',
            'committee_decision_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'is_overdue' => 'boolean',
            'is_escalated' => 'boolean',
            'is_auto_escalated' => 'boolean',
            'is_dissatisfied' => 'boolean',
        ];
    }

    public function complainant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'complainant_user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function assignedCommitteeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_committee_user_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ComplaintResponse::class);
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(ComplaintEscalation::class);
    }

    public function committeeDecisions(): HasMany
    {
        return $this->hasMany(ComplaintCommitteeDecision::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ComplaintStatusHistory::class)->latest('acted_at');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->can('complaints.view_all') || $user->isSuperAdmin()) {
            return $query;
        }

        return $query->where(function ($builder) use ($user): void {
            if ($user->can('complaints.view_department')) {
                $builder->orWhere('department_id', $user->department_id);
            }

            if ($user->can('complaints.committee.review') || $user->can('complaints.committee.decide')) {
                $builder->orWhere('is_escalated', true);
            }

            if ($user->can('complaints.view_own') || $user->can('complaints.create')) {
                $builder->orWhere('complainant_user_id', $user->getKey());
            }
        });
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [ComplaintStatus::RESOLVED, ComplaintStatus::CLOSED], true);
    }

    public function canForwardToCommittee(): bool
    {
        return $this->status === ComplaintStatus::DEPARTMENT_RESPONDED
            && ! $this->is_escalated;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'branch_id',
                'department_id',
                'assigned_committee_user_id',
                'complainant_user_id',
                'status',
                'is_overdue',
                'is_escalated',
                'is_auto_escalated',
                'is_dissatisfied',
                'department_response_deadline_at',
                'department_responded_at',
                'forwarded_to_committee_at',
                'committee_review_started_at',
                'committee_decision_at',
                'resolved_at',
                'closed_at',
            ])
            ->logOnlyDirty();
    }
}
