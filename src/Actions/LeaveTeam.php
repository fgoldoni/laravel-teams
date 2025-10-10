<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Exceptions\CannotLeaveTeam;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

final readonly class LeaveTeam
{
    public function __construct(private RemoveTeamMember $removeTeamMember)
    {
    }

    public function handle(Model $model, Authenticatable $authenticatable): void
    {
        try {
            Gate::authorize('leave', $model);

            $userId = (int) $authenticatable->getAuthIdentifier();

            if ((int) $model->getAttribute('owner_id') === $userId) {
                throw new CannotLeaveTeam('Owner cannot leave the team.');
            }

            DB::transaction(function () use ($model, $authenticatable): void {
                $this->removeTeamMember->handle($model, $authenticatable);
            });
        } catch (AuthorizationException|Throwable $e) {
            throw new CannotLeaveTeam($e->getMessage(), 0, $e);
        }
    }
}
