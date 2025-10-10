<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberRemoved
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Model $team, public Model $user)
    {
    }
}
