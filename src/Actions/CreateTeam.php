<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Goldoni\LaravelTeams\Events\TeamCreated;
use Goldoni\LaravelTeams\Exceptions\CannotCreateTeam;
use Goldoni\LaravelTeams\Models\Team;
use Goldoni\LaravelTeams\Models\TeamUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class CreateTeam
{
    public function __construct(private Team $team, private TeamUser $teamUser)
    {
    }

    public function handle(Authenticatable $authenticatable, string $name): Team
    {
        $ownerId = (int) $authenticatable->getAuthIdentifier();
        $name    = trim($name);

        try {
            $team = DB::transaction(function () use ($ownerId, $name, $authenticatable): Team {
                $team = $this->team->newQuery()->create([
                    'name'     => $name,
                    'owner_id' => $ownerId,
                ]);

                $this->teamUser->newQuery()->create([
                    'team_id' => $team->getKey(),
                    'user_id' => $ownerId,
                    'role'    => TeamRoleEnum::OWNER,
                ]);

                $authenticatable->forceFill(['current_team_id' => $team->getKey()])->save();

                return $team;
            });

            DB::afterCommit(fn () => TeamCreated::dispatch($team));

            return $team;
        } catch (Throwable $throwable) {
            throw new CannotCreateTeam($throwable->getMessage(), 0, $throwable);
        }
    }
}
