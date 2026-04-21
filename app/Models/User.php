<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidPrimaryKey;
use App\Enums\LocaleCode;
use App\Enums\SystemRole;
use App\Enums\TeamType;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
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
    use SoftDeletes;

    protected $fillable = [
        'department_id',
        'team_id',
        'branch_id',
        'employee_number',
        'name',
        'email',
        'phone',
        'telegram_chat_id',
        'avatar_path',
        'signature_path',
        'stamp_path',
        'job_title',
        'national_id',
        'telegram_username',
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
            'deleted_at' => 'datetime',
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
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

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'complainant_user_id');
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
                'avatar_path',
                'signature_path',
                'stamp_path',
                'job_title',
                'national_id',
                'telegram_username',
                'locale',
                'is_active',
            ])
            ->logOnlyDirty();
    }

    public function avatarUrl(): ?string
    {
        return $this->mediaUrl($this->avatar_path);
    }

    public function signatureUrl(): ?string
    {
        return $this->mediaUrl($this->signature_path);
    }

    public function stampUrl(): ?string
    {
        return $this->mediaUrl($this->stamp_path);
    }

    public static function formatNationalId(?string $nationalId): ?string
    {
        if (! is_string($nationalId)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $nationalId) ?? '';

        if ($digits === '') {
            return null;
        }

        return trim((string) preg_replace('/(\d{4})(?=\d)/', '$1 ', $digits));
    }

    public function hasSystemRole(SystemRole $role): bool
    {
        return $this->hasRole($role->value);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function canAnyPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($permission !== '' && $this->can($permission)) {
                return true;
            }
        }

        return false;
    }

    public function hasGlobalAdvisoryVisibility(): bool
    {
        return $this->canAnyPermissions([
            'advisory.review',
            'advisory-requests.review',
            'advisory.assign_team_leader',
            'audit.view',
        ]);
    }

    public function hasAdvisoryAdministrativeAccess(): bool
    {
        return $this->canAnyPermissions([
            'advisory.review',
            'advisory-requests.review',
            'advisory.assign_team_leader',
        ]);
    }

    public function canLeadAdvisoryWorkflow(): bool
    {
        return $this->team?->type === TeamType::ADVISORY
            && $this->canAnyPermissions([
                'advisory.assign_expert',
                'advisory-requests.assign',
            ]);
    }

    public function canRespondToAdvisories(): bool
    {
        return $this->team?->type === TeamType::ADVISORY
            && $this->canAnyPermissions([
                'advisory.respond',
                'advisory-requests.respond',
            ]);
    }

    public function usesRequesterAdvisoryScope(): bool
    {
        return ! $this->hasAdvisoryAdministrativeAccess()
            && ! $this->hasGlobalAdvisoryVisibility()
            && ! $this->canLeadAdvisoryWorkflow()
            && ! $this->canRespondToAdvisories()
            && $this->canAnyPermissions([
                'advisory.create',
                'advisory-requests.create',
            ]);
    }

    public function hasGlobalCaseVisibility(): bool
    {
        return $this->canAnyPermissions([
            'cases.review',
            'legal-cases.review',
            'cases.assign_team_leader',
            'audit.view',
        ]);
    }

    public function hasCaseAdministrativeAccess(): bool
    {
        return $this->canAnyPermissions([
            'cases.review',
            'legal-cases.review',
            'cases.assign_team_leader',
        ]);
    }

    public function canLeadLitigationWorkflow(): bool
    {
        return $this->team?->type === TeamType::LITIGATION
            && $this->canAnyPermissions([
                'cases.assign_expert',
                'legal-cases.assign',
            ]);
    }

    public function canHandleAssignedCases(): bool
    {
        return $this->team?->type === TeamType::LITIGATION
            && $this->canAnyPermissions([
                'cases.record_hearing',
                'legal-cases.update',
            ]);
    }

    public function canRegisterCases(): bool
    {
        return $this->canAnyPermissions([
            'cases.create',
            'legal-cases.create',
        ]);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasSystemRole(SystemRole::SUPER_ADMIN);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function scopeWithAnyPermission(Builder $query, array $permissions): Builder
    {
        $permissions = array_values(array_unique(array_filter($permissions, fn (mixed $permission): bool => is_string($permission) && $permission !== '')));

        if ($permissions === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $builder) use ($permissions): void {
            $builder
                ->whereHas('permissions', fn (Builder $permissionQuery) => $permissionQuery->whereIn('name', $permissions))
                ->orWhereHas('roles.permissions', fn (Builder $permissionQuery) => $permissionQuery->whereIn('name', $permissions));
        });
    }

    public function scopeEligibleAdvisoryTeamLeaders(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereHas('team', fn (Builder $teamQuery) => $teamQuery->where('type', TeamType::ADVISORY->value))
            ->withAnyPermission(['advisory.assign_expert', 'advisory-requests.assign'])
            ->distinct();
    }

    public function scopeEligibleAdvisoryExperts(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereHas('team', fn (Builder $teamQuery) => $teamQuery->where('type', TeamType::ADVISORY->value))
            ->withAnyPermission(['advisory.respond', 'advisory-requests.respond'])
            ->distinct();
    }

    public function scopeEligibleLitigationTeamLeaders(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereHas('team', fn (Builder $teamQuery) => $teamQuery->where('type', TeamType::LITIGATION->value))
            ->withAnyPermission(['cases.assign_expert', 'legal-cases.assign'])
            ->distinct();
    }

    public function scopeEligibleLitigationExperts(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereHas('team', fn (Builder $teamQuery) => $teamQuery->where('type', TeamType::LITIGATION->value))
            ->withAnyPermission(['cases.record_hearing', 'legal-cases.update'])
            ->distinct();
    }

    private function mediaUrl(?string $path): ?string
    {
        if (! is_string($path)) {
            return null;
        }

        $normalizedPath = trim($path);

        if ($normalizedPath === '' || str_contains($normalizedPath, '..') || ! Str::startsWith($normalizedPath, 'users/')) {
            return null;
        }

        return route('branding-assets.show', ['path' => $normalizedPath]);
    }
}
