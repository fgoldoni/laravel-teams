<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Policies;

use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Goldoni\LaravelTeams\Models\Team;
use Illuminate\Contracts\Auth\Authenticatable;

class TeamPolicy
{
    public function view(?Authenticatable $user, Team $team): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->id === $team->owner_id) {
            return true;
        }

        return $team->users()->whereKey($user->getAuthIdentifier())->exists();
    }

    public function create(Authenticatable $user): bool
    {
        $limit = (int) config('teams.max_teams_per_user', 0);
        if ($limit <= 0) {
            return true;
        }

        $count = app(config('auth.providers.users.model'))::query()
            ->findOrFail($user->getAuthIdentifier())
            ->teams()
            ->count();

        return $count < $limit;
    }

    public function update(Authenticatable $user, Team $team): bool
    {
        if ($user->id === $team->owner_id) {
            return true;
        }

        $role = $team->memberships()
            ->where('user_id', $user->getAuthIdentifier())
            ->value('role');

        return in_array($role, [TeamRoleEnum::OWNER->value, TeamRoleEnum::ADMIN->value], true);
    }

    public function manageMembers(Authenticatable $user, Team $team): bool
    {
        if ($user->id === $team->owner_id) {
            return true;
        }

        $role = $team->memberships()
            ->where('user_id', $user->getAuthIdentifier())
            ->value('role');

        return $role === TeamRoleEnum::ADMIN->value;
    }

    public function delete(Authenticatable $user, Team $team): bool
    {
        return $user->id === $team->owner_id;
    }
}
