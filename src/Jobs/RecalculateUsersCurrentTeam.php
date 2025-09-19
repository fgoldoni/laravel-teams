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

    public function __construct(public array $userIds, public int $removedTeamId)
    {
    }

    public function handle(): void
    {
        $userClass = config('auth.providers.users.model');
        $users     = $userClass::query()
            ->whereIn('id', $this->userIds)
            ->where('current_team_id', $this->removedTeamId)
            ->get();

        $users->each(function ($user): void {
            $nextOwned = $user->ownedTeams()
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->first();

            if ($nextOwned instanceof Team) {
                $user->forceFill(['current_team_id' => $nextOwned->getKey()])->save();

                return;
            }

            $nextMember = $user->teams()
                ->whereNull('teams.deleted_at')
                ->orderBy('teams.id')
                ->first();

            if ($nextMember instanceof Team) {
                $user->forceFill(['current_team_id' => $nextMember->getKey()])->save();

                return;
            }

            $user->forceFill(['current_team_id' => null])->save();
        });
    }
}
