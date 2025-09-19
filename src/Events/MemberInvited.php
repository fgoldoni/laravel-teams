<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Events;

use Goldoni\LaravelTeams\Models\Team;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MemberInvited
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Team $team, public object $invitee, public string $role)
    {
    }
}
