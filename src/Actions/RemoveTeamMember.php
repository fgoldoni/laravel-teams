<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Events\MemberRemoved;
use Goldoni\LaravelTeams\Exceptions\CannotRemoveMember;
use Goldoni\LaravelTeams\Support\ResolveModel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

final class RemoveTeamMember
{
    public function handle(Model $team, Model $model): void
    {
        try {
            Gate::authorize('manageMembers', $team);

            $teamUserClass = ResolveModel::teamUser();

            $affected = DB::transaction(function () use ($teamUserClass, $team, $model): int {
                $deleted = $teamUserClass::query()
                    ->where('team_id', $team->getKey())
                    ->where('user_id', $model->getKey())
                    ->delete();

                if ((int) $model->getAttribute('current_team_id') === (int) $team->getKey()) {
                    $model->forceFill(['current_team_id' => null])->save();
                }

                return $deleted;
            });

            if ($affected === 0) {
                throw new CannotRemoveMember('Membership not found.');
            }

            DB::afterCommit(fn () => MemberRemoved::dispatch($team, $model));
        } catch (AuthorizationException|Throwable $e) {
            throw new CannotRemoveMember($e->getMessage(), 0, $e);
        }
    }
}
