<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Goldoni\LaravelTeams\Events\MemberAdded;
use Goldoni\LaravelTeams\Models\Team;
use Goldoni\LaravelTeams\Models\TeamUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class AddTeamMember
{
    public function __construct(private readonly TeamUser $teamUser)
    {
    }

    public function handle(Team $team, Model $model, TeamRoleEnum $teamRoleEnum = TeamRoleEnum::MEMBER): TeamUser
    {
        Gate::authorize('manageMembers', $team);

        $teamUser = $this->teamUser->newQuery()->firstOrCreate(
            ['team_id' => $team->id, 'user_id' => $model->getKey()],
            ['role' => $teamRoleEnum->value]
        );

        if ($teamUser->wasRecentlyCreated) {
            MemberAdded::dispatch($team, $model, $teamRoleEnum);

            if (config('teams.invite_notifications', false)) {
                $model->notify(new \Goldoni\LaravelTeams\Notifications\MemberAdded($team, $teamRoleEnum));
            }
        }

        return $teamUser;
    }
}
