<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidPrimaryKey;
use App\Enums\AdvisoryRequestType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\User;

class AdvisoryResponse extends Model
{
    use HasFactory;
    use HasUuidPrimaryKey;

    protected $fillable = [
        'advisory_request_id',
        'responder_id',
        'subject',
        'response',
        'response_type',
        'summary',
        'advice_text',
        'follow_up_notes',
        'responded_at',
        'approval_status',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'response_type' => AdvisoryRequestType::class,
            'responded_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function advisoryRequest(): BelongsTo
    {
        return $this->belongsTo(AdvisoryRequest::class);
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responder_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
