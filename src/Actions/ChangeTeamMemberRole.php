<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Goldoni\LaravelTeams\Events\MemberRoleChanged;
use Goldoni\LaravelTeams\Exceptions\CannotChangeMemberRole;
use Goldoni\LaravelTeams\Support\ResolveModel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

final class ChangeTeamMemberRole
{
    public function handle(Model $team, Model $model, TeamRoleEnum $teamRoleEnum): void
    {
        try {
            Gate::authorize('manageMembers', $team);

            $teamUserClass = ResolveModel::teamUser();

            $affected = DB::transaction(fn (): int => $teamUserClass::query()
                ->where('team_id', $team->getKey())
                ->where('user_id', $model->getKey())
                ->update(['role' => $teamRoleEnum]));

            if ($affected === 0) {
                throw new CannotChangeMemberRole('Membership not found.');
            }

            DB::afterCommit(fn () => MemberRoleChanged::dispatch($team, $model, $teamRoleEnum));
        } catch (AuthorizationException|Throwable $e) {
            throw new CannotChangeMemberRole($e->getMessage(), 0, $e);
        }
    }
}
