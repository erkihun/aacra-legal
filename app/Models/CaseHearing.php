<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CaseHearing extends Model
{
    use HasFactory;
    use HasUuidPrimaryKey;

    protected $fillable = [
        'legal_case_id',
        'recorded_by_id',
        'hearing_date',
        'next_hearing_date',
        'appearance_status',
        'summary',
        'institution_position',
        'court_decision',
        'outcome',
    ];

    protected function casts(): array
    {
        return [
            'hearing_date' => 'date',
            'next_hearing_date' => 'date',
        ];
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
