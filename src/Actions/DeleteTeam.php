<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Events\TeamDeleted;
use Goldoni\LaravelTeams\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeleteTeam
{
    public function handle(Team $team): void
    {
        Gate::authorize('delete', $team);

        DB::transaction(function () use ($team): void {
            $team->memberships()->delete();
            $team->delete();
            TeamDeleted::dispatch($team->getKey());
        });
    }
}
