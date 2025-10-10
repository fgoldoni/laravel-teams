<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Observers;

use Goldoni\LaravelTeams\Jobs\RecalculateUsersCurrentTeam;
use Illuminate\Database\Eloquent\Model;

class TeamObserver
{
    public function deleted(Model $model): void
    {
        $this->recalculateCurrentTeamForAffectedUsers($model);
    }

    public function forceDeleted(Model $model): void
    {
        $this->recalculateCurrentTeamForAffectedUsers($model);
    }

    private function recalculateCurrentTeamForAffectedUsers(Model $model): void
    {
        $userIdentifiers         = $model->users()->pluck('users.id')->all();
        $ownerIdentifierList     = $model->getAttribute('owner_id') ? [$model->getAttribute('owner_id')] : [];
        $affectedUserIdentifiers = array_values(array_unique(array_merge($userIdentifiers, $ownerIdentifierList)));

        if ($affectedUserIdentifiers !== []) {
            RecalculateUsersCurrentTeam::dispatchSync($affectedUserIdentifiers, (int) $model->getKey());
        }
    }
}
