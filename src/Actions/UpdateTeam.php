<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Models\Team;
use Illuminate\Support\Facades\Gate;

class UpdateTeam
{
    public function handle(Team $team, string $name): Team
    {
        Gate::authorize('update', $team);
        $team->forceFill(['name' => $name])->save();

        return $team;
    }
}
