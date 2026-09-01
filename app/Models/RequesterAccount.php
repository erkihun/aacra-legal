<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidPrimaryKey;
use App\Notifications\RequesterResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class RequesterAccount extends Authenticatable
{
    use HasFactory;
    use HasUuidPrimaryKey;
    use Notifiable;
    use SoftDeletes;

    protected $table = 'requester_accounts';

    protected $fillable = [
        'department_id',
        'full_name',
        'email',
        'phone',
        'job_title',
        'password',
        'is_active',
        'email_verified_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Send the password reset notification that links into the requester portal.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new RequesterResetPasswordNotification($token));
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function advisoryRequests(): HasMany
    {
        return $this->hasMany(AdvisoryRequest::class, 'requester_account_id');
    }

    public function lawsuitRequests(): HasMany
    {
        return $this->hasMany(LawsuitFilingRequest::class, 'requester_account_id');
    }
}
