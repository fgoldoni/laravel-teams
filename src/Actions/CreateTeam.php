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
    public function __construct(private readonly Team $team, private readonly TeamUser $teamUser)
    {
    }

    public function handle(Authenticatable $authenticatable, string $name): Team
    {
        return DB::transaction(function () use ($authenticatable, $name): Team {
            $team = $this->team->newQuery()->create([
                'name'     => $name,
                'owner_id' => $authenticatable->getAuthIdentifier(),
            ]);

            $this->teamUser->newQuery()->create([
                'team_id' => $team->id,
                'user_id' => $authenticatable->getAuthIdentifier(),
                'role'    => TeamRoleEnum::OWNER->value,
            ]);

            $authenticatable->forceFill(['current_team_id' => $team->id])->save();

            TeamCreated::dispatch($team);

            return $team;
        });
    }
}
