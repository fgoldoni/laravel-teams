<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Events;

final readonly class TeamsHealthCheckFailed
{
    public function __construct(public array $checks)
    {
    }
}
