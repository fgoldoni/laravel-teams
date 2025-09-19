<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Goldoni\LaravelTeams\Events\MemberAdded;
use Goldoni\LaravelTeams\Exceptions\CannotAddMember;
use Goldoni\LaravelTeams\Models\Team;
use Goldoni\LaravelTeams\Models\TeamUser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

final readonly class AddTeamMember
{
    public function __construct(private TeamUser $teamUser)
    {
    }

    public function handle(Team $team, Model $model, TeamRoleEnum $teamRoleEnum = TeamRoleEnum::MEMBER): TeamUser
    {
        try {
            Gate::authorize('manageMembers', $team);

            $created = false;

            $teamUser = DB::transaction(function () use ($team, $model, $teamRoleEnum, &$created): TeamUser {
                $teamUser = $this->teamUser->newQuery()->firstOrCreate(
                    ['team_id' => $team->getKey(), 'user_id' => $model->getKey()],
                    ['role' => $teamRoleEnum]
                );

                $created = $teamUser->wasRecentlyCreated;

                return $teamUser;
            });

            if ($created) {
                DB::afterCommit(function () use ($team, $model, $teamRoleEnum): void {
                    MemberAdded::dispatch($team, $model, $teamRoleEnum);

                    if (config('teams.invite_notifications', false) && method_exists($model, 'notify')) {
                        $model->notify(new \Goldoni\LaravelTeams\Notifications\MemberAdded($team, $teamRoleEnum));
                    }
                });
            }

            return $teamUser;
        } catch (AuthorizationException|Throwable $e) {
            throw new CannotAddMember($e->getMessage(), 0, $e);
        }
    }
}
