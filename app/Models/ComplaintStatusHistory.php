<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidPrimaryKey;
use App\Enums\ComplaintStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintStatusHistory extends Model
{
    use HasFactory;
    use HasUuidPrimaryKey;

    public $timestamps = false;

    protected $fillable = [
        'complaint_id',
        'actor_id',
        'from_status',
        'to_status',
        'action',
        'notes',
        'metadata',
        'acted_at',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => ComplaintStatus::class,
            'to_status' => ComplaintStatus::class,
            'metadata' => 'array',
            'acted_at' => 'datetime',
        ];
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
