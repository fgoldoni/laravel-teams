<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Goldoni\LaravelTeams\Events\MemberRoleChanged;
use Goldoni\LaravelTeams\Models\Team;
use Illuminate\Database\Eloquent\Model;

class ChangeTeamMemberRole
{
    public function handle(Team $team, Model $user, TeamRoleEnum $role): void
    {
        $team->memberships()
            ->where('user_id', $user->getKey())
            ->update(['role' => $role->value]);

        MemberRoleChanged::dispatch($team, $user, $role);
    }
}
