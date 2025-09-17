<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Goldoni\LaravelTeams\Events\TeamCreated;
use Goldoni\LaravelTeams\Models\Team;
use Goldoni\LaravelTeams\Models\TeamUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

class CreateTeam
{
    public function __construct(private readonly Team $teams, private readonly TeamUser $teamUsers)
    {
    }

    public function handle(Authenticatable $owner, string $name): Team
    {
        return DB::transaction(function () use ($owner, $name): Team {
            $team = $this->teams->newQuery()->create([
                'name' => $name,
                'owner_id' => $owner->getAuthIdentifier(),
            ]);

            $this->teamUsers->newQuery()->create([
                'team_id' => $team->id,
                'user_id' => $owner->getAuthIdentifier(),
                'role' => TeamRoleEnum::OWNER->value,
            ]);

            $owner->forceFill(['current_team_id' => $team->id])->save();

            TeamCreated::dispatch($team);

            return $team;
        });
    }
}
