<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Exceptions\CannotUpdateTeam;
use Goldoni\LaravelTeams\Models\Team;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

final class UpdateTeam
{
    public function handle(Team $team, string $name): Team
    {
        try {
            Gate::authorize('update', $team);

            DB::transaction(function () use ($team, $name): void {
                $team->forceFill(['name' => trim($name)])->save();
            });

            return $team;
        } catch (AuthorizationException|Throwable $e) {
            throw new CannotUpdateTeam($e->getMessage(), 0, $e);
        }
    }
}
