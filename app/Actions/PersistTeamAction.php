<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Team;

class PersistTeamAction
{
    public function execute(?Team $team, array $attributes): Team
    {
        $team ??= new Team;
        $team->fill($attributes);
        $team->save();

        return $team->refresh()->loadMissing('leader');
    }
}
