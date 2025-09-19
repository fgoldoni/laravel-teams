<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Goldoni\LaravelTeams\Events\TeamOwnershipTransferred;
use Goldoni\LaravelTeams\Exceptions\CannotTransferOwnership;
use Goldoni\LaravelTeams\Models\Team;
use Goldoni\LaravelTeams\Models\TeamUser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

final readonly class TransferOwnership
{
    public function __construct(private TeamUser $teamUser)
    {
    }

    public function handle(Team $team, Model $model): void
    {
        try {
            Gate::authorize('transferOwnership', $team);

            $oldOwnerId = (int) $team->owner_id;
            $newOwnerId = (int) $model->getKey();

            if ($oldOwnerId === $newOwnerId) {
                throw new CannotTransferOwnership('Already owner.');
            }

            DB::transaction(function () use ($team, $oldOwnerId, $newOwnerId): void {
                $existing = $this->teamUser->newQuery()
                    ->withTrashed()
                    ->where('team_id', $team->getKey())
                    ->where('user_id', $newOwnerId)
                    ->first();

                if ($existing) {
                    if (method_exists($existing, 'trashed') && $existing->trashed()) {
                        $existing->restore();
                    }

                    $existing->forceFill(['role' => TeamRoleEnum::OWNER])->save();
                } else {
                    $this->teamUser->newQuery()->create([
                        'team_id' => $team->getKey(),
                        'user_id' => $newOwnerId,
                        'role'    => TeamRoleEnum::OWNER,
                    ]);
                }

                $this->teamUser->newQuery()
                    ->where('team_id', $team->getKey())
                    ->where('user_id', $oldOwnerId)
                    ->update(['role' => TeamRoleEnum::ADMIN]);

                $team->forceFill(['owner_id' => $newOwnerId])->save();
            });

            DB::afterCommit(fn () => TeamOwnershipTransferred::dispatch($team, $oldOwnerId, $newOwnerId));
        } catch (AuthorizationException|Throwable $e) {
            throw new CannotTransferOwnership($e->getMessage(), 0, $e);
        }
    }
}
