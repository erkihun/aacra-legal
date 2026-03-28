<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidPrimaryKey;
use App\Enums\LocaleCode;
use App\Enums\SystemRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use HasUuidPrimaryKey;
    use LogsActivity;
    use Notifiable;

    protected $fillable = [
        'department_id',
        'team_id',
        'employee_number',
        'name',
        'email',
        'phone',
        'telegram_chat_id',
        'job_title',
        'locale',
        'email_verified_at',
        'last_login_at',
        'is_active',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'locale' => LocaleCode::class,
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function requestedAdvisories(): HasMany
    {
        return $this->hasMany(AdvisoryRequest::class, 'requester_user_id');
    }

    public function assignedAdvisories(): HasMany
    {
        return $this->hasMany(AdvisoryRequest::class, 'assigned_legal_expert_id');
    }

    public function registeredCases(): HasMany
    {
        return $this->hasMany(LegalCase::class, 'registered_by_id');
    }

    public function assignedCases(): HasMany
    {
        return $this->hasMany(LegalCase::class, 'assigned_legal_expert_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'department_id',
                'team_id',
                'employee_number',
                'name',
                'email',
                'phone',
                'telegram_chat_id',
                'job_title',
                'locale',
                'is_active',
            ])
            ->logOnlyDirty();
    }

    public function hasSystemRole(SystemRole $role): bool
    {
        return $this->hasRole($role->value);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasSystemRole(SystemRole::SUPER_ADMIN);
    }
}
