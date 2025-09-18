<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Goldoni\LaravelTeams\Events\MemberRoleChanged;
use Goldoni\LaravelTeams\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class ChangeTeamMemberRole
{
    public function handle(Team $team, Model $model, TeamRoleEnum $teamRoleEnum): void
    {
        Gate::authorize('manageMembers', $team);

        $team->memberships()
            ->where('user_id', $model->getKey())
            ->update(['role' => $teamRoleEnum->value]);

        MemberRoleChanged::dispatch($team, $model, $teamRoleEnum);
    }
}
