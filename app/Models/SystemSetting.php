<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidPrimaryKey;
use App\Enums\SystemSettingGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class SystemSetting extends Model
{
    use HasFactory;
    use HasUuidPrimaryKey;
    use LogsActivity;

    protected $fillable = [
        'setting_group',
        'setting_key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'setting_group' => SystemSettingGroup::class,
            'value' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['setting_group', 'setting_key', 'value'])
            ->logOnlyDirty();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        if ($this->setting_key !== 'bot_token') {
            return;
        }

        $properties = $activity->properties->toArray();

        foreach (['attributes', 'old'] as $propertyGroup) {
            if (isset($properties[$propertyGroup]['value'])) {
                $properties[$propertyGroup]['value'] = ['value' => '[REDACTED]'];
            }
        }

        $activity->properties = $properties;
    }
}
