<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Models\Team;
use Illuminate\Contracts\Auth\Authenticatable;

class SwitchTeam
{
    public function handle(Authenticatable $authenticatable, Team $team): void
    {
        if ($team->users()->whereKey($authenticatable->getAuthIdentifier())->doesntExist()
            && $team->owner_id !== $authenticatable->getAuthIdentifier()) {
            return;
        }

        $authenticatable->forceFill(['current_team_id' => $team->id])->save();
    }
}
