<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

interface TeamsManager
{
    public function current(?Authenticatable $authenticatable = null): ?Model;

    public function forUser(Authenticatable $authenticatable): ?Model;

    public function isOwner(Authenticatable $authenticatable, Model $model): bool;
}
