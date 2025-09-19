<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Goldoni\LaravelTeams\Events\InviteAccepted;
use Goldoni\LaravelTeams\Exceptions\CannotAcceptInvite;
use Goldoni\LaravelTeams\Models\Team;
use Goldoni\LaravelTeams\Models\TeamUser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

final readonly class AcceptInvite
{
    public function __construct(private AddTeamMember $addTeamMember)
    {
    }

    public function handle(Team $team, Model $model, TeamRoleEnum $teamRoleEnum = TeamRoleEnum::MEMBER): void
    {
        try {
            Gate::authorize('acceptInvite', $team);

            $membership = DB::transaction(fn (): TeamUser => $this->addTeamMember->handle($team, $model, $teamRoleEnum));

            DB::afterCommit(fn () => InviteAccepted::dispatch($team, $model, (string) $membership->role->value));
        } catch (AuthorizationException|Throwable $e) {
            throw new CannotAcceptInvite($e->getMessage(), 0, $e);
        }
    }
}
