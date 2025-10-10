<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Services;

use Goldoni\LaravelTeams\Contracts\TeamsManager as TeamsManagerContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class TeamsManager implements TeamsManagerContract
{
    public function current(?Authenticatable $authenticatable = null): ?Model
    {
        $authenticatable = $authenticatable ?: auth()->user();

        if (! $authenticatable) {
            return null;
        }

        return $authenticatable->currentTeam;
    }

    public function forUser(Authenticatable $authenticatable): ?Model
    {
        return $authenticatable->currentTeam;
    }

    public function isOwner(Authenticatable $authenticatable, Model $model): bool
    {
        return (int) $model->getAttribute('owner_id') === (int) $authenticatable->getAuthIdentifier();
    }
}
