<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Exceptions\CannotSwitchTeam;
use Goldoni\LaravelTeams\Models\Team;
use Illuminate\Contracts\Auth\Authenticatable;

final class SwitchTeam
{
    public function handle(Authenticatable $authenticatable, Team $team): void
    {
        $userId = (int) $authenticatable->getAuthIdentifier();

        $isMember = $team->users()->whereKey($userId)->exists();
        $isOwner  = (int) $team->owner_id === $userId;

        if (! $isMember && ! $isOwner) {
            throw new CannotSwitchTeam('User does not belong to this team.');
        }

        $authenticatable->forceFill(['current_team_id' => $team->getKey()])->save();
    }
}
