<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamDeleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public int $teamId)
    {
    }
}
