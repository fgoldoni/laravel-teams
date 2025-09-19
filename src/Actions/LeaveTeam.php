<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Exceptions\CannotLeaveTeam;
use Goldoni\LaravelTeams\Models\Team;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

final readonly class LeaveTeam
{
    public function __construct(private RemoveTeamMember $removeTeamMember)
    {
    }

    public function handle(Team $team, Authenticatable $authenticatable): void
    {
        try {
            Gate::authorize('leave', $team);

            $userId = (int) $authenticatable->getAuthIdentifier();

            if ((int) $team->owner_id === $userId) {
                throw new CannotLeaveTeam('Owner cannot leave the team.');
            }

            DB::transaction(function () use ($team, $authenticatable): void {
                $this->removeTeamMember->handle($team, $authenticatable);
            });
        } catch (AuthorizationException|Throwable $e) {
            throw new CannotLeaveTeam($e->getMessage(), 0, $e);
        }
    }
}
