<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidPrimaryKey;
use App\Enums\TeamType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Team extends Model
{
    use HasFactory;
    use HasUuidPrimaryKey;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'leader_user_id',
        'code',
        'name_en',
        'name_am',
        'type',
        'supports_advisory',
        'supports_court_case',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => TeamType::class,
            'supports_advisory' => 'boolean',
            'supports_court_case' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_user_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSupportingAdvisory(Builder $query): Builder
    {
        return $query->where('supports_advisory', true);
    }

    public function scopeSupportingCourtCase(Builder $query): Builder
    {
        return $query->where('supports_court_case', true);
    }

    public function supportsAdvisory(): bool
    {
        return (bool) $this->supports_advisory;
    }

    public function supportsCourtCase(): bool
    {
        return (bool) $this->supports_court_case;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
