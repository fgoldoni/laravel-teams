<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Concerns;

use Goldoni\LaravelTeams\Models\Team;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasTeams
{
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->withPivot(['id', 'ulid', 'role'])
            ->withTimestamps();
    }

    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    public function switchTeam(Team $team): void
    {
        if (! $this->teams()->whereKey($team->id)->exists()) {
            return;
        }

        $this->forceFill(['current_team_id' => $team->id])->save();
    }

    public function isOnTeam(Team $team): bool
    {
        return $this->teams()->whereKey($team->id)->exists();
    }
}
