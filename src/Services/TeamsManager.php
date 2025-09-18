<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Services;

use Goldoni\LaravelTeams\Contracts\TeamsManager as TeamsManagerContract;
use Goldoni\LaravelTeams\Models\Team;
use Illuminate\Contracts\Auth\Authenticatable;

class TeamsManager implements TeamsManagerContract
{
    public function current(?Authenticatable $authenticatable = null): ?Team
    {
        $u = $authenticatable ?: auth()->user();

        if (! $u) {
            return null;
        }

        return $u->currentTeam;
    }

    public function forUser(Authenticatable $authenticatable): ?Team
    {
        return $authenticatable->currentTeam;
    }

    public function isOwner(Authenticatable $authenticatable, Team $team): bool
    {
        return $team->owner_id === $authenticatable->getAuthIdentifier();
    }
}
