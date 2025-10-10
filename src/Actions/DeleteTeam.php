<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Events\TeamDeleted;
use Goldoni\LaravelTeams\Exceptions\CannotDeleteTeam;
use Goldoni\LaravelTeams\Support\ResolveModel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

final class DeleteTeam
{
    public function handle(Model $model): void
    {
        try {
            Gate::authorize('delete', $model);

            $teamUserClass = ResolveModel::teamUser();

            DB::transaction(function () use ($teamUserClass, $model): void {
                $teamUserClass::query()->where('team_id', $model->getKey())->delete();
                $model->delete();
            });

            DB::afterCommit(fn () => TeamDeleted::dispatch($model->getKey()));
        } catch (AuthorizationException|Throwable $e) {
            throw new CannotDeleteTeam($e->getMessage(), 0, $e);
        }
    }
}
