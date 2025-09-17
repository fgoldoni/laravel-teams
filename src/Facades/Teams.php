<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Facades;

use Illuminate\Support\Facades\Facade;

class Teams extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'goldoni.teams';
    }
}
