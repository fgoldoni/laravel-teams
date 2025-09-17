<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Goldoni\LaravelTeams\Events\MemberAdded;
use Goldoni\LaravelTeams\Models\Team;
use Goldoni\LaravelTeams\Models\TeamUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;

class AddTeamMember
{
    public function __construct(private readonly TeamUser $teamUsers)
    {
    }

    public function handle(Team $team, Model $user, TeamRoleEnum $role = TeamRoleEnum::MEMBER): TeamUser
    {
        $membership = $this->teamUsers->newQuery()->create([
            'team_id' => $team->id,
            'user_id' => $user->getKey(),
            'role' => $role->value,
        ]);

        MemberAdded::dispatch($team, $user, $role);

        if ((bool) config('teams.invite_notifications', false)) {
            $user->notify(new \Goldoni\LaravelTeams\Notifications\MemberAdded($team, $role));
        }

        return $membership;
    }
}
