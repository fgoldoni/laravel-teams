<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Events;

use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Goldoni\LaravelTeams\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberAdded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Team $team, public Model $user, public TeamRoleEnum $role)
    {
    }
}
