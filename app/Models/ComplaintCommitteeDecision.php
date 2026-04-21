<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidPrimaryKey;
use App\Enums\ComplaintCommitteeOutcome;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplaintCommitteeDecision extends Model
{
    use HasFactory;
    use HasUuidPrimaryKey;
    use SoftDeletes;

    protected $fillable = [
        'complaint_id',
        'committee_actor_id',
        'investigation_notes',
        'decision_summary',
        'decision_detail',
        'decision_date',
        'outcome',
    ];

    protected function casts(): array
    {
        return [
            'decision_date' => 'datetime',
            'outcome' => ComplaintCommitteeOutcome::class,
        ];
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function committeeActor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'committee_actor_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
