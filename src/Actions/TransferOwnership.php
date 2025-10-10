<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Goldoni\LaravelTeams\Events\TeamOwnershipTransferred;
use Goldoni\LaravelTeams\Exceptions\CannotTransferOwnership;
use Goldoni\LaravelTeams\Support\ResolveModel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

final class TransferOwnership
{
    public function handle(Model $team, Model $model): void
    {
        try {
            Gate::authorize('transferOwnership', $team);

            $oldOwnerId = (int) $team->getAttribute('owner_id');
            $newOwnerId = (int) $model->getKey();

            if ($oldOwnerId === $newOwnerId) {
                throw new CannotTransferOwnership('Already owner.');
            }

            $teamUserClass = ResolveModel::teamUser();

            DB::transaction(function () use ($teamUserClass, $team, $oldOwnerId, $newOwnerId): void {
                $existing = $teamUserClass::query()
                    ->withTrashed()
                    ->where('team_id', $team->getKey())
                    ->where('user_id', $newOwnerId)
                    ->first();

                if ($existing) {
                    if (\method_exists($existing, 'trashed') && $existing->trashed()) {
                        $existing->restore();
                    }

                    $existing->forceFill(['role' => TeamRoleEnum::OWNER])->save();
                } else {
                    $teamUserClass::query()->create([
                        'team_id' => $team->getKey(),
                        'user_id' => $newOwnerId,
                        'role'    => TeamRoleEnum::OWNER,
                    ]);
                }

                $teamUserClass::query()
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
