<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Concerns;

use Goldoni\LaravelTeams\Models\Team;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

trait HasTeams
{
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->withPivot(['id', 'ulid', 'role'])
            ->withTimestamps();
    }

    public function ownedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'owner_id');
    }

    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    public function belongsToTeam(Team $team): bool
    {
        if ($team->owner_id === $this->getKey()) {
            return true;
        }

        return $this->teams()->whereKey($team->getKey())->exists();
    }

    public function switchTeam(Team $team): bool
    {
        if (! $this->belongsToTeam($team)) {
            return false;
        }

        $this->forceFill(['current_team_id' => $team->getKey()])->save();
        $this->setRelation('currentTeam', $team);

        return true;
    }

    public function isOnTeam(Team $team): bool
    {
        return $this->belongsToTeam($team);
    }

    public function ownsTeam(Team $team): bool
    {
        return $team->owner_id === $this->getKey();
    }

    public function isCurrentTeam(Team $team): bool
    {
        return (int) $this->getAttribute('current_team_id') === (int) $team->getKey();
    }

    public function allTeams(): Collection
    {
        $owned  = $this->ownedTeams()->get();
        $member = $this->teams()->get();

        return $owned->merge($member)->unique('id')->values();
    }
}
