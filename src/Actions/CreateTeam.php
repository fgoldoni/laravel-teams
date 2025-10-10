<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Goldoni\LaravelTeams\Events\TeamCreated;
use Goldoni\LaravelTeams\Exceptions\CannotCreateTeam;
use Goldoni\LaravelTeams\Support\ResolveModel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class CreateTeam
{
    public function handle(Authenticatable $authenticatable, string $name): Model
    {
        $ownerId = (int) $authenticatable->getAuthIdentifier();
        $name    = \trim($name);

        try {
            $teamClass     = ResolveModel::team();
            $teamUserClass = ResolveModel::teamUser();

            $team = DB::transaction(function () use ($teamClass, $teamUserClass, $ownerId, $name, $authenticatable): Model {
                $team = $teamClass::query()->create([
                    'ulid'     => (string) Str::ulid(),
                    'name'     => $name,
                    'owner_id' => $ownerId,
                ]);

                $teamUserClass::query()->create([
                    'ulid'    => (string) Str::ulid(),
                    'team_id' => $team->getKey(),
                    'user_id' => $ownerId,
                    'role'    => TeamRoleEnum::OWNER,
                ]);

                $authenticatable->forceFill(['current_team_id' => $team->getKey()])->save();

                return $team;
            });

            DB::afterCommit(static fn () => TeamCreated::dispatch($team));

            return $team;
        } catch (Throwable $throwable) {
            throw new CannotCreateTeam($throwable->getMessage(), 0, $throwable);
        }
    }
}
