<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Concerns;

use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Goldoni\LaravelTeams\Models\Team;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
        if (!empty($this->getAttribute('current_team_id')) || !$this->exists) {
            return $this->belongsTo(Team::class, 'current_team_id');
        }

        if ($this->relationLoaded('ownedTeams') && $this->ownedTeams->isNotEmpty()) {
            $fallback = $this->ownedTeams->sortBy('id')->first();
        } else {
            $fallback = $this->ownedTeams()->oldest('id')->first();
        }

        if (!$fallback) {
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

    public function belongsToTeam(Model $model): bool
    {
        if ((int)$model->owner_id === (int)$this->getKey()) {
            return true;
        }

        return $this->teams()->whereKey($model->getKey())->exists();
    }

    public function switchTeam(Model $model): bool
    {
        if (!$this->belongsToTeam($model)) {
            return false;
        }

        $this->forceFill(['current_team_id' => $model->getKey()])->save();
        $this->setRelation('currentTeam', $model);

        return true;
    }

    public function isOnTeam(Model $model): bool
    {
        return $this->belongsToTeam($model);
    }

    public function ownsTeam(Model $model): bool
    {
        return (int)$model->owner_id === (int)$this->getKey();
    }

    public function isCurrentTeam(Model $model): bool
    {
        return (int)$this->getAttribute('current_team_id') === (int)$model->getKey();
    }

    public function allTeams(): Collection
    {
        $owned = $this->ownedTeams()->get();
        $member = $this->teams()->get();

        return $owned->merge($member)->unique('id')->values();
    }

    public function teamRole(Model $model): ?TeamRoleEnum
    {
        if ((int)$model->owner_id === (int)$this->getKey()) {
            return TeamRoleEnum::OWNER;
        }

        $role = $model->relationLoaded('memberships')
            ? optional($model->memberships->firstWhere('user_id', $this->getKey()))->role
            : $model->memberships()->where('user_id', $this->getKey())->value('role');

        if ($role instanceof TeamRoleEnum) {
            return $role;
        }

        return $role !== null ? TeamRoleEnum::tryFrom((string)$role) : null;
    }

    public function hasTeamRole(Model $model, TeamRoleEnum|string $role): bool
    {
        $current = $this->teamRole($model);

        if ($current === null) {
            return false;
        }

        $expected = $role instanceof TeamRoleEnum ? $role : TeamRoleEnum::from($role);

        return $current === $expected;
    }

    public function hasAnyTeamRole(Model $model, array $roles): bool
    {
        $current = $this->teamRole($model);

        if ($current === null) {
            return false;
        }

        $expected = array_map(
            fn($r) => $r instanceof TeamRoleEnum ? $r : TeamRoleEnum::from((string)$r),
            $roles
        );

        return in_array($current, $expected, true);
    }

    public function myTeamIdsAtLeast(TeamRoleEnum $min): QueryBuilder
    {
        return DB::table('team_user')
            ->select('team_id')
            ->where('user_id', $this->getKey())
            ->whereIn('role', self::rolesAtLeast($min));
    }

    public function scopeSharesTeamWithActorAtLeast(
        EloquentBuilder $query,
        Model|Authenticatable $actor,
        TeamRoleEnum $min
    ): EloquentBuilder {
        $eligibleTeamIds = $actor->myTeamIdsAtLeast($min);

        return $query->whereHas('teams', fn($t) => $t->whereIn('teams.id', $eligibleTeamIds));
    }

    protected static function rolesAtLeast(TeamRoleEnum $min): array
    {
        $minRank = self::roleRank($min);

        return array_map(
            static fn(TeamRoleEnum $e) => $e->value,
            array_filter(TeamRoleEnum::cases(), static fn(TeamRoleEnum $e) => self::roleRank($e) >= $minRank)
        );
    }

    protected static function roleRank(TeamRoleEnum $teamRoleEnum): int
    {
        return match ($teamRoleEnum) {
            TeamRoleEnum::VIEWER => 1,
            TeamRoleEnum::MEMBER => 2,
            TeamRoleEnum::ADMIN => 3,
            TeamRoleEnum::OWNER => 4,
        };
    }

    public function hasTeamRoleAtLeast(Model $model, TeamRoleEnum|string $min): bool
    {
        $current = $this->teamRole($model);

        if ($current === null) {
            return false;
        }

        $minEnum = $min instanceof TeamRoleEnum ? $min : TeamRoleEnum::from($min);

        return self::roleRank($current) >= self::roleRank($minEnum);
    }

    public function hasTeamRoleOwner(Model $model): bool
    {
        return $this->hasTeamRole($model, TeamRoleEnum::OWNER);
    }

    public function hasTeamRoleAdmin(Model $model): bool
    {
        return $this->hasTeamRoleAtLeast($model, TeamRoleEnum::ADMIN);
    }

    public function hasTeamRoleMember(Model $model): bool
    {
        return $this->hasTeamRoleAtLeast($model, TeamRoleEnum::MEMBER);
    }

    public function hasTeamRoleViewer(Model $model): bool
    {
        return $this->hasTeamRoleAtLeast($model, TeamRoleEnum::VIEWER);
    }

    public function hasTeamRoleManagerial(Model $model): bool
    {
        return $this->hasTeamRoleAtLeast($model, TeamRoleEnum::ADMIN);
    }
}
