<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Jobs;

use Goldoni\LaravelTeams\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateUsersCurrentTeam implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public array $userIdentifiers, public int $removedTeamIdentifier)
    {
    }

    public function handle(): void
    {
        $userModelClass = config('auth.providers.users.model');

        $usersCollection = $userModelClass::query()
            ->whereIn('id', $this->userIdentifiers)
            ->where('current_team_id', $this->removedTeamIdentifier)
            ->get();

        $usersCollection->each(function ($user): void {
            $nextOwnedTeam = $user->ownedTeams()
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->first();

            if ($nextOwnedTeam instanceof Team) {
                $user->forceFill(['current_team_id' => $nextOwnedTeam->getKey()])->save();

                return;
            }

            $nextMemberTeam = $user->teams()
                ->whereNull('teams.deleted_at')
                ->orderBy('teams.id')
                ->first();

            if ($nextMemberTeam instanceof Team) {
                $user->forceFill(['current_team_id' => $nextMemberTeam->getKey()])->save();

                return;
            }

            $user->forceFill(['current_team_id' => null])->save();
        });
    }
}
