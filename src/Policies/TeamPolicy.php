<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Policies;

use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Goldoni\LaravelTeams\Models\Team;
use Illuminate\Contracts\Auth\Authenticatable;

class TeamPolicy
{
    public function view(?Authenticatable $authenticatable, Team $team): bool
    {
        if (!$authenticatable instanceof Authenticatable) {
            return false;
        }

        if ($authenticatable->id === $team->owner_id) {
            return true;
        }

        return $team->users()->whereKey($authenticatable->getAuthIdentifier())->exists();
    }

    public function create(Authenticatable $authenticatable): bool
    {
        $limit = (int) config('teams.max_teams_per_user', 0);

        if ($limit <= 0) {
            return true;
        }

        $count = app(config('auth.providers.users.model'))::query()
            ->findOrFail($authenticatable->getAuthIdentifier())
            ->teams()
            ->count();

        return $count < $limit;
    }

    public function update(Authenticatable $authenticatable, Team $team): bool
    {
        if ($authenticatable->id === $team->owner_id) {
            return true;
        }

        $role = $team->memberships()
            ->where('user_id', $authenticatable->getAuthIdentifier())
            ->value('role');

        return in_array($role, [TeamRoleEnum::OWNER->value, TeamRoleEnum::ADMIN->value], true);
    }

    public function manageMembers(Authenticatable $authenticatable, Team $team): bool
    {
        if ($authenticatable->id === $team->owner_id) {
            return true;
        }

        $role = $team->memberships()
            ->where('user_id', $authenticatable->getAuthIdentifier())
            ->value('role');

        return $role === TeamRoleEnum::ADMIN->value;
    }

    public function delete(Authenticatable $authenticatable, Team $team): bool
    {
        return $authenticatable->id === $team->owner_id;
    }
}
