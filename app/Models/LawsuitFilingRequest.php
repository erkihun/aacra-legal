<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidPrimaryKey;
use App\Enums\LawsuitRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LawsuitFilingRequest extends Model
{
    use HasFactory;
    use HasUuidPrimaryKey;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'request_code',
        'requesting_department_id',
        'created_by',
        'reviewed_by',
        'requester_account_id',
        'letter_template_id',
        'letter_snapshot',
        'subject',
        'description',
        'status',
        'reviewer_notes',
        'date_submitted',
    ];

    protected function casts(): array
    {
        return [
            'status' => LawsuitRequestStatus::class,
            'letter_snapshot' => 'array',
            'date_submitted' => 'date',
        ];
    }

    public function requestingDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'requesting_department_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function requesterAccount(): BelongsTo
    {
        return $this->belongsTo(RequesterAccount::class, 'requester_account_id');
    }

    public function letterTemplate(): BelongsTo
    {
        return $this->belongsTo(LetterTemplate::class, 'letter_template_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('lawsuit-requests.review') || $user->hasGlobalCaseVisibility()) {
            return $query;
        }

        return $query->where('created_by', $user->getKey());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['requesting_department_id', 'created_by', 'subject', 'status', 'reviewed_by'])
            ->logOnlyDirty();
    }
}
