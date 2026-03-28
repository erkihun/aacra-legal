<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidPrimaryKey;
use App\Enums\AdvisoryRequestType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvisoryResponse extends Model
{
    use HasFactory;
    use HasUuidPrimaryKey;

    protected $fillable = [
        'advisory_request_id',
        'responder_id',
        'response_type',
        'summary',
        'advice_text',
        'follow_up_notes',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'response_type' => AdvisoryRequestType::class,
            'responded_at' => 'datetime',
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
}
