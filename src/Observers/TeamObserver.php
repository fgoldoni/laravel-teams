<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Observers;

use Goldoni\LaravelTeams\Jobs\RecalculateUsersCurrentTeam;
use Goldoni\LaravelTeams\Models\Team;

class TeamObserver
{
    public function deleted(Team $team): void
    {
        $userIds  = $team->users()->pluck('users.id')->all();
        $ownerId  = $team->owner_id ? [$team->owner_id] : [];
        $affected = array_values(array_unique(array_merge($userIds, $ownerId)));

        if ($affected !== []) {
            RecalculateUsersCurrentTeam::dispatch($affected, $team->getKey());
        }
    }
}
