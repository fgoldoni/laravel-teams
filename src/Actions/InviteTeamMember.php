<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Goldoni\LaravelTeams\Events\MemberInvited;
use Goldoni\LaravelTeams\Exceptions\CannotInviteMember;
use Goldoni\LaravelTeams\Models\Team;
use Goldoni\LaravelTeams\Notifications\MemberAdded;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

final class InviteTeamMember
{
    public function handle(Team $team, Model $model, TeamRoleEnum $teamRoleEnum = TeamRoleEnum::MEMBER): void
    {
        try {
            Gate::authorize('invite', $team);

            DB::transaction(function () use ($team, $model, $teamRoleEnum): void {
            });

            DB::afterCommit(function () use ($team, $model, $teamRoleEnum): void {
                MemberInvited::dispatch($team, $model, $teamRoleEnum->value);

                if (config('teams.invite_notifications', false) && method_exists($model, 'notify')) {
                    $model->notify(new MemberAdded($team, $teamRoleEnum));
                }
            });
        } catch (AuthorizationException|Throwable $e) {
            throw new CannotInviteMember($e->getMessage(), 0, $e);
        }
    }
}
