<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SystemSettingGroup;
use App\Models\User;
use App\Services\SystemSettingsService;

class UpdateSystemSettingsGroupAction
{
    public function __construct(
        private readonly SystemSettingsService $settings,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(SystemSettingGroup $group, array $payload, User $actor): array
    {
        activity()
            ->causedBy($actor)
            ->withProperties([
                'group' => $group->value,
                'keys' => array_keys($payload),
            ])
            ->log('Settings group updated.');

        $updated = $this->settings->updateGroup($group, $payload);
        $this->settings->applyRuntimeConfiguration();

        return $updated;
    }
}
