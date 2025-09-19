<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Observers;

use Goldoni\LaravelTeams\Jobs\RecalculateUsersCurrentTeam;
use Goldoni\LaravelTeams\Models\Team;

class TeamObserver
{
    public function deleted(Team $team): void
    {
        $this->recalculateCurrentTeamForAffectedUsers($team);
    }

    public function forceDeleted(Team $team): void
    {
        $this->recalculateCurrentTeamForAffectedUsers($team);
    }

    private function recalculateCurrentTeamForAffectedUsers(Team $team): void
    {
        $userIdentifiers         = $team->users()->pluck('users.id')->all();
        $ownerIdentifierList     = $team->owner_id ? [$team->owner_id] : [];
        $affectedUserIdentifiers = array_values(array_unique(array_merge($userIdentifiers, $ownerIdentifierList)));

        if ($affectedUserIdentifiers !== []) {
            RecalculateUsersCurrentTeam::dispatchSync($affectedUserIdentifiers, $team->getKey());
        }
    }
}
