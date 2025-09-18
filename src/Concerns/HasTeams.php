<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Concerns;

use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
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
        if (! empty($this->getAttribute('current_team_id')) || ! $this->exists) {
            return $this->belongsTo(Team::class, 'current_team_id');
        }

        if ($this->relationLoaded('ownedTeams') && $this->ownedTeams->isNotEmpty()) {
            $fallback = $this->ownedTeams->sortBy('id')->first();
        } else {
            $fallback = $this->ownedTeams()->oldest('id')->first();
        }

        if (! $fallback) {
            if ($this->relationLoaded('teams') && $this->teams->isNotEmpty()) {
                $fallback = $this->teams->sortBy('id')->first();
            } else {
                $fallback = $this->teams()->select('teams.*')->oldest('teams.id')->first();
            }
        }

        if ($fallback instanceof Team) {
            $this->switchTeam($fallback);
        }

        return $this->belongsTo(Team::class, 'current_team_id');
    }


    public function belongsToTeam(Team $team): bool
    {
        if ((int) $team->owner_id === (int) $this->getKey()) {
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
        return (int) $team->owner_id === (int) $this->getKey();
    }

    public function isCurrentTeam(Team $team): bool
    {
        return (int) $this->getAttribute('current_team_id') === (int) $team->getKey();
    }

    public function allTeams(): Collection
    {
        $owned = $this->ownedTeams()->get();
        $member = $this->teams()->get();

        return $owned->merge($member)->unique('id')->values();
    }

    public function teamRole(Team $team): ?TeamRoleEnum
    {
        if ((int) $team->owner_id === (int) $this->getKey()) {
            return TeamRoleEnum::OWNER;
        }

        $role = $team->relationLoaded('memberships')
            ? optional($team->memberships->firstWhere('user_id', $this->getKey()))->role
            : $team->memberships()->where('user_id', $this->getKey())->value('role');

        return $role ? TeamRoleEnum::tryFrom((string) $role) : null;
    }

    public function hasTeamRole(Team $team, TeamRoleEnum|string $role): bool
    {
        $current = $this->teamRole($team);
        if ($current === null) {
            return false;
        }

        $expected = $role instanceof TeamRoleEnum ? $role : TeamRoleEnum::from((string) $role);
        return $current === $expected;
    }

    public function hasAnyTeamRole(Team $team, array $roles): bool
    {
        $current = $this->teamRole($team);
        if ($current === null) {
            return false;
        }

        $expected = array_map(
            fn ($r) => $r instanceof TeamRoleEnum ? $r : TeamRoleEnum::from((string) $r),
            $roles
        );

        return in_array($current, $expected, true);
    }

    private function roleRank(TeamRoleEnum $role): int
    {
        return match ($role) {
            TeamRoleEnum::VIEWER => 1,
            TeamRoleEnum::MEMBER => 2,
            TeamRoleEnum::ADMIN => 3,
            TeamRoleEnum::OWNER => 4,
        };
    }

    public function hasTeamRoleAtLeast(Team $team, TeamRoleEnum|string $min): bool
    {
        $current = $this->teamRole($team);
        if ($current === null) {
            return false;
        }

        $minEnum = $min instanceof TeamRoleEnum ? $min : TeamRoleEnum::from((string) $min);

        return $this->roleRank($current) >= $this->roleRank($minEnum);
    }

    public function hasTeamRoleOwner(Team $team): bool
    {
        return $this->hasTeamRole($team, TeamRoleEnum::OWNER);
    }

    public function hasTeamRoleAdmin(Team $team): bool
    {
        return $this->hasTeamRoleAtLeast($team, TeamRoleEnum::ADMIN);
    }

    public function hasTeamRoleMember(Team $team): bool
    {
        return $this->hasTeamRoleAtLeast($team, TeamRoleEnum::MEMBER);
    }

    public function hasTeamRoleViewer(Team $team): bool
    {
        return $this->hasTeamRoleAtLeast($team, TeamRoleEnum::VIEWER);
    }

    public function hasTeamRoleManagerial(Team $team): bool
    {
        return $this->hasTeamRoleAtLeast($team, TeamRoleEnum::ADMIN);
    }
}

