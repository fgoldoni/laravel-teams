<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Facades;

use Goldoni\LaravelTeams\Contracts\TeamsManager;
use Illuminate\Support\Facades\Facade;

class Teams extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TeamsManager::class;
    }
}
