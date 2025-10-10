<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Exceptions\CannotUpdateTeam;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

final class UpdateTeam
{
    public function handle(Model $model, string $name): Model
    {
        try {
            Gate::authorize('update', $model);

            DB::transaction(function () use ($model, $name): void {
                $model->forceFill(['name' => \trim($name)])->save();
            });

            return $model;
        } catch (AuthorizationException|Throwable $e) {
            throw new CannotUpdateTeam($e->getMessage(), 0, $e);
        }
    }
}
