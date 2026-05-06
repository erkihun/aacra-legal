<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TeamType;
use App\Models\Team;

class PersistTeamAction
{
    public function execute(?Team $team, array $attributes): Team
    {
        $team ??= new Team;
        $attributes['type'] = $this->resolveLegacyType($team, $attributes);
        $team->fill($attributes);
        $team->save();

        return $team->refresh()->loadMissing('leader');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function resolveLegacyType(Team $team, array $attributes): string
    {
        $supportsAdvisory = (bool) ($attributes['supports_advisory'] ?? false);
        $supportsCourtCase = (bool) ($attributes['supports_court_case'] ?? false);

        return match (true) {
            $supportsAdvisory && ! $supportsCourtCase => TeamType::ADVISORY->value,
            ! $supportsAdvisory && $supportsCourtCase => TeamType::LITIGATION->value,
            $supportsAdvisory && $supportsCourtCase => $team->type?->value ?? TeamType::ADMINISTRATION->value,
            default => $team->type?->value ?? TeamType::ADMINISTRATION->value,
        };
    }
}
