<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Events\TeamDeleted;
use Goldoni\LaravelTeams\Exceptions\CannotDeleteTeam;
use Goldoni\LaravelTeams\Models\Team;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

final class DeleteTeam
{
    public function handle(Team $team): void
    {
        try {
            Gate::authorize('delete', $team);

            DB::transaction(function () use ($team): void {
                $team->memberships()->delete();
                $team->delete();
            });

            DB::afterCommit(fn () => TeamDeleted::dispatch($team->getKey()));
        } catch (AuthorizationException|Throwable $e) {
            throw new CannotDeleteTeam($e->getMessage(), 0, $e);
        }
    }
}
