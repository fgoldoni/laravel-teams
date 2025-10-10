<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Policies;

use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Goldoni\LaravelTeams\Support\ResolveModel;
use Goldoni\ModelPermissions\Policies\BaseModelPolicy;
use Illuminate\Database\Eloquent\Model;
use Override;

final class TeamPolicy extends BaseModelPolicy
{
    protected string $modelClass;

    public function __construct()
    {
        $this->modelClass = ResolveModel::team();
    }

    public function before(Model $user, string $ability): ?bool
    {
        if ($this->isUserInstance($user) && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    #[Override]
    public function viewAny(Model $model): bool
    {
        return $this->hasPermissionTo($model, 'viewAny');
    }

    #[Override]
    public function view(Model $user, Model $team): bool
    {
        if (! $this->isUserInstance($user) || ! $this->isTeamInstance($team)) {
            return false;
        }

        if (! $this->hasPermissionTo($user, 'view', $team)) {
            return false;
        }

        return $user->hasTeamRoleAtLeast($team, TeamRoleEnum::MEMBER);
    }

    #[Override]
    public function create(Model $model): bool
    {
        if (! $this->isUserInstance($model)) {
            return false;
        }

        if (! $this->hasPermissionTo($model, 'create')) {
            return false;
        }

        $max = (int) config('teams.max_teams_per_user', 0);

        if ($max === 0) {
            return true;
        }

        return $this->userAllTeamsCount($model) < $max;
    }

    #[Override]
    public function update(Model $user, Model $team): bool
    {
        if (! $this->isUserInstance($user) || ! $this->isTeamInstance($team)) {
            return false;
        }

        if (! $this->hasPermissionTo($user, 'update', $team)) {
            return false;
        }

        return $user->hasTeamRoleAtLeast($team, TeamRoleEnum::ADMIN);
    }

    #[Override]
    public function delete(Model $user, Model $team): bool
    {
        if (! $this->isUserInstance($user) || ! $this->isTeamInstance($team)) {
            return false;
        }

        if (! $this->hasPermissionTo($user, 'delete', $team)) {
            return false;
        }

        return $user->hasTeamRoleAtLeast($team, TeamRoleEnum::ADMIN);
    }

    #[Override]
    public function restore(Model $user, Model $team): bool
    {
        if (! $this->isUserInstance($user) || ! $this->isTeamInstance($team)) {
            return false;
        }

        return $this->hasPermissionTo($user, 'restore', $team) && $user->hasTeamRoleAtLeast($team, TeamRoleEnum::ADMIN);
    }

    #[Override]
    public function forceDelete(Model $user, Model $team): bool
    {
        if (! $this->isUserInstance($user) || ! $this->isTeamInstance($team)) {
            return false;
        }

        return $this->hasPermissionTo($user, 'forceDelete', $team) && $user->hasTeamRoleAtLeast($team, TeamRoleEnum::ADMIN);
    }

    public function manageMembers(Model $user, Model $team): bool
    {
        if (! $this->isUserInstance($user) || ! $this->isTeamInstance($team)) {
            return false;
        }

        if ($this->isOwnerOrAdmin($user, $team)) {
            return true;
        }

        if ($this->hasPermissionTo($user, 'attachAny') || $this->hasPermissionTo($user, 'detachAny')) {
            return true;
        }

        $canAttach = $this->hasPermissionTo($user, 'attach', $team) && $this->userIsOnTeam($user, $team);
        $canDetach = $this->hasPermissionTo($user, 'detach', $team) && $this->userIsOnTeam($user, $team);

        return $canAttach || $canDetach;
    }

    public function transferOwnership(Model $user, Model $team): bool
    {
        if (! $this->isUserInstance($user) || ! $this->isTeamInstance($team)) {
            return false;
        }

        if (! $this->userIsOnTeam($user, $team)) {
            return false;
        }

        if ($this->isOwnerOrAdmin($user, $team)) {
            return true;
        }

        return $this->hasPermissionTo($user, 'transferOwnership', $team);
    }

    public function invite(Model $user, Model $team): bool
    {
        if (! $this->isUserInstance($user) || ! $this->isTeamInstance($team)) {
            return false;
        }

        if (! $this->userIsOnTeam($user, $team)) {
            return false;
        }

        if ($this->isOwnerOrAdmin($user, $team)) {
            return true;
        }

        return $this->hasPermissionTo($user, 'invite', $team);
    }

    public function leave(Model $user, Model $team): bool
    {
        if (! $this->isUserInstance($user) || ! $this->isTeamInstance($team)) {
            return false;
        }

        if (! $this->userIsOnTeam($user, $team)) {
            return false;
        }

        if ($this->isOwner($user, $team)) {
            return false;
        }

        return $this->hasPermissionTo($user, 'leave', $team);
    }

    public function acceptInvite(Model $user, Model $team): bool
    {
        if (! $this->isUserInstance($user) || ! $this->isTeamInstance($team)) {
            return false;
        }

        return $this->hasPermissionTo($user, 'acceptInvite', $team);
    }

    public function declineInvite(Model $user, Model $team): bool
    {
        if (! $this->isUserInstance($user) || ! $this->isTeamInstance($team)) {
            return false;
        }

        return $this->hasPermissionTo($user, 'declineInvite', $team);
    }

    private function userIsOnTeam(Model $user, Model $team): bool
    {
        return $this->isUserInstance($user) && $this->isTeamInstance($team) && $user->isOnTeam($team);
    }

    private function userAllTeamsCount(Model $user): int
    {
        return $this->isUserInstance($user) ? $user->allTeams()->count() : 0;
    }

    private function isOwner(Model $user, Model $team): bool
    {
        return $this->isUserInstance($user) && $this->isTeamInstance($team) && $user->ownsTeam($team);
    }

    private function isOwnerOrAdmin(Model $user, Model $team): bool
    {
        if (! $this->isUserInstance($user) || ! $this->isTeamInstance($team)) {
            return false;
        }

        if ($user->ownsTeam($team)) {
            return true;
        }

        return $user->hasTeamRoleAdmin($team);
    }

    private function isUserInstance(Model $model): bool
    {
        $userClass = ResolveModel::user();

        return $model instanceof $userClass;
    }

    private function isTeamInstance(Model $model): bool
    {
        $teamClass = ResolveModel::team();

        return $model instanceof $teamClass;
    }
}
