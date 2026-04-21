<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidPrimaryKey;
use App\Enums\ComplaintEscalationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintEscalation extends Model
{
    use HasFactory;
    use HasUuidPrimaryKey;

    public $timestamps = false;

    protected $fillable = [
        'complaint_id',
        'escalated_by_id',
        'escalation_type',
        'reason',
        'escalated_at',
    ];

    protected function casts(): array
    {
        return [
            'escalation_type' => ComplaintEscalationType::class,
            'escalated_at' => 'datetime',
        ];
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function escalatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_by_id');
    }
}
