<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidPrimaryKey;
use App\Enums\SystemSettingGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
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
}
