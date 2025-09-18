<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Events\MemberRemoved;
use Goldoni\LaravelTeams\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class RemoveTeamMember
{
    public function handle(Team $team, Model $model): void
    {
        Gate::authorize('manageMembers', $team);

        $team->memberships()->where('user_id', $model->getKey())->delete();

        if ($model->getAttribute('current_team_id') === $team->getKey()) {
            $model->forceFill(['current_team_id' => null])->save();
        }

        MemberRemoved::dispatch($team, $model);
    }
}
