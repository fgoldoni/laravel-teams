<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Models\Team;

class UpdateTeam
{
    public function handle(Team $team, string $name): Team
    {
        $team->forceFill(['name' => $name])->save();

        return $team;
    }
}
