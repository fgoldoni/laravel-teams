<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Events\InviteDeclined;
use Goldoni\LaravelTeams\Exceptions\CannotDeclineInvite;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

final class DeclineInvite
{
    public function handle(Model $team, Model $model): void
    {
        try {
            Gate::authorize('declineInvite', $team);
            DB::transaction(static function (): void {});
            DB::afterCommit(fn () => InviteDeclined::dispatch($team, $model));
        } catch (AuthorizationException|Throwable $e) {
            throw new CannotDeclineInvite($e->getMessage(), 0, $e);
        }
    }
}
