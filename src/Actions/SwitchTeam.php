<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Actions;

use Goldoni\LaravelTeams\Exceptions\CannotSwitchTeam;
use Goldoni\LaravelTeams\Support\ResolveModel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class SwitchTeam
{
    public function handle(Authenticatable $authenticatable, Model $model): void
    {
        $userId = (int) $authenticatable->getAuthIdentifier();

        $teamUserClass = ResolveModel::teamUser();
        $isMember      = $teamUserClass::query()
            ->where('team_id', $model->getKey())
            ->where('user_id', $userId)
            ->exists();

        $isOwner = (int) $model->getAttribute('owner_id') === $userId;

        if (! $isMember && ! $isOwner) {
            throw new CannotSwitchTeam('User does not belong to this team.');
        }

        $authenticatable->forceFill(['current_team_id' => $model->getKey()])->save();
    }
}
