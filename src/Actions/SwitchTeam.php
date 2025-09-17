<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Models\Team;
use Illuminate\Contracts\Auth\Authenticatable;

class SwitchTeam
{
    public function handle(Authenticatable $user, Team $team): void
    {
        if ($team->users()->whereKey($user->getAuthIdentifier())->doesntExist()
            && $team->owner_id !== $user->getAuthIdentifier()) {
            return;
        }

        $user->forceFill(['current_team_id' => $team->id])->save();
    }
}
