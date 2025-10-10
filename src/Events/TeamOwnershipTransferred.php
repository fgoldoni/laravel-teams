<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TeamOwnershipTransferred
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $team,
        public int $oldOwnerId,
        public int $newOwnerId
    ) {
    }
}
