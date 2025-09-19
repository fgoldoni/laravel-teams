<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Events;

final readonly class TeamsHealthCheckPassed
{
    public function __construct(public array $checks)
    {
    }
}
