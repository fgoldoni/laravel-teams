<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Goldoni\LaravelTeams\Events\MemberAdded;
use Goldoni\LaravelTeams\Exceptions\CannotAddMember;
use Goldoni\LaravelTeams\Support\ResolveModel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Throwable;

final class AddTeamMember
{
    public function handle(
        Model $team,
        Model $model,
        TeamRoleEnum $teamRoleEnum = TeamRoleEnum::MEMBER,
        bool $skipAuthorization = false
    ): Model {
        try {
            if (! $skipAuthorization) {
                Gate::authorize('manageMembers', $team);
            }

            $teamUserClass = ResolveModel::teamUser();
            $wasCreated    = false;

            $membership = DB::transaction(function () use ($teamUserClass, $team, $model, $teamRoleEnum, &$wasCreated): Model {
                $row = $teamUserClass::query()->firstOrCreate(
                    ['team_id' => $team->getKey(), 'user_id' => $model->getKey()],
                    ['role' => $teamRoleEnum, 'ulid' => (string) Str::ulid()]
                );

                $wasCreated = $row->wasRecentlyCreated;

                return $row;
            });

            if ($wasCreated) {
                DB::afterCommit(function () use ($team, $model, $teamRoleEnum): void {
                    MemberAdded::dispatch($team, $model, $teamRoleEnum);

                    if (\config('teams.invite_notifications', false) && \method_exists($model, 'notify')) {
                        $model->notify(new \Goldoni\LaravelTeams\Notifications\MemberAdded($team, $teamRoleEnum));
                    }
                });
            }

            return $membership;
        } catch (AuthorizationException|Throwable $e) {
            throw new CannotAddMember($e->getMessage(), 0, $e);
        }
    }
}
