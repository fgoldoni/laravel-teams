<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Events\MemberRemoved;
use Goldoni\LaravelTeams\Models\Team;
use Illuminate\Database\Eloquent\Model;

class RemoveTeamMember
{
    public function handle(Team $team, Model $user): void
    {
        $team->memberships()->where('user_id', $user->getKey())->delete();

        if ($user->getAttribute('current_team_id') === $team->getKey()) {
            $user->forceFill(['current_team_id' => null])->save();
        }

        MemberRemoved::dispatch($team, $user);
    }
}
