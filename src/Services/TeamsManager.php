<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Services;

use Goldoni\LaravelTeams\Models\Team;
use Illuminate\Contracts\Auth\Authenticatable;

class TeamsManager
{
    public function current(?Authenticatable $user = null): ?Team
    {
        $u = $user ?: auth()->user();

        if (! $u) {
            return null;
        }

        return $u->currentTeam;
    }

    public function forUser(Authenticatable $user): ?Team
    {
        return $user->currentTeam;
    }

    public function isOwner(Authenticatable $user, Team $team): bool
    {
        return $team->owner_id === $user->getAuthIdentifier();
    }
}
