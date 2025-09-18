<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Contracts;

use Goldoni\LaravelTeams\Models\Team;
use Illuminate\Contracts\Auth\Authenticatable;

interface TeamsManager
{
    public function current(?Authenticatable $authenticatable = null): ?Team;

    public function forUser(Authenticatable $authenticatable): ?Team;

    public function isOwner(Authenticatable $authenticatable, Team $team): bool;
}
