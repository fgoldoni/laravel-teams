<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Concerns;

use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Goldoni\LaravelTeams\Support\ResolveModel;
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
        return $this->belongsToMany(ResolveModel::team(), 'team_user')
            ->withPivot(['id', 'ulid', 'role'])
            ->withTimestamps();
    }

    public function ownedTeams(): HasMany
    {
        return $this->hasMany(ResolveModel::team(), 'owner_id');
    }

    public function currentTeam(): BelongsTo
    {
        if (! empty($this->getAttribute('current_team_id')) || ! $this->exists) {
            return $this->belongsTo(ResolveModel::team(), 'current_team_id');
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

        if ($fallback) {
            $this->switchTeam($fallback);
        }

        return $this->belongsTo(ResolveModel::team(), 'current_team_id');
    }

    public function belongsToTeam(Model $model): bool
    {
        if ((int) $model->owner_id === (int) $this->getKey()) {
            return true;
        }

        return $this->teams()->whereKey($model->getKey())->exists();
    }

    public function switchTeam(Model $model): bool
    {
        if (! $this->belongsToTeam($model)) {
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
        return (int) $model->owner_id === (int) $this->getKey();
    }

    public function isCurrentTeam(Model $model): bool
    {
        return (int) $this->getAttribute('current_team_id') === (int) $model->getKey();
    }

    public function allTeams(): Collection
    {
        $owned  = $this->ownedTeams()->get();
        $member = $this->teams()->get();

        return $owned->merge($member)->unique('id')->values();
    }

    public function teamRole(Model $model): ?TeamRoleEnum
    {
        if ((int) $model->owner_id === (int) $this->getKey()) {
            return TeamRoleEnum::OWNER;
        }

        $role = $model->relationLoaded('memberships')
            ? optional($model->memberships->firstWhere('user_id', $this->getKey()))->role
            : $model->memberships()->where('user_id', $this->getKey())->value('role');

        if ($role instanceof TeamRoleEnum) {
            return $role;
        }

        return $role !== null ? TeamRoleEnum::tryFrom((string) $role) : null;
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
            fn ($r) => $r instanceof TeamRoleEnum ? $r : TeamRoleEnum::from((string) $r),
            $roles
        );

        return in_array($current, $expected, true);
    }

    public function scopeVisibleToActor(
        EloquentBuilder $eloquentBuilder,
        Authenticatable $authenticatable,
        TeamRoleEnum $minActorRole = TeamRoleEnum::ADMIN,
        TeamRoleEnum $minTargetRole = TeamRoleEnum::VIEWER,
        bool $onlyCurrentTeam = true
    ): EloquentBuilder {
        $eligibleTeamIds = $authenticatable->myTeamIdsAtLeast($minActorRole)->select('team_id');

        if ($onlyCurrentTeam && ! empty($authenticatable->current_team_id)) {
            $eligibleTeamIds->where('team_id', $authenticatable->current_team_id);
        }

        $rolesAtLeast = match ($minTargetRole) {
            TeamRoleEnum::VIEWER => [
                TeamRoleEnum::VIEWER->value,
                TeamRoleEnum::MEMBER->value,
                TeamRoleEnum::ADMIN->value,
                TeamRoleEnum::OWNER->value,
            ],
            TeamRoleEnum::MEMBER => [
                TeamRoleEnum::MEMBER->value,
                TeamRoleEnum::ADMIN->value,
                TeamRoleEnum::OWNER->value,
            ],
            TeamRoleEnum::ADMIN => [
                TeamRoleEnum::ADMIN->value,
                TeamRoleEnum::OWNER->value,
            ],
            TeamRoleEnum::OWNER => [
                TeamRoleEnum::OWNER->value,
            ],
        };

        return $eloquentBuilder->where(function ($outer) use ($eligibleTeamIds, $rolesAtLeast): void {
            $outer->whereHas('teams', function ($q) use ($eligibleTeamIds, $rolesAtLeast): void {
                $q->select('teams.id')
                    ->whereIn('teams.id', $eligibleTeamIds)
                    ->whereIn('team_user.role', $rolesAtLeast);
            })->orWhereExists(function ($q) use ($eligibleTeamIds): void {
                $q->from('teams')
                    ->select('teams.id')
                    ->whereColumn('teams.owner_id', 'users.id')
                    ->whereIn('teams.id', $eligibleTeamIds);
            });
        });
    }

    public function myTeamIdsAtLeast(TeamRoleEnum $teamRoleEnum): QueryBuilder
    {
        return DB::table('team_user')
            ->select('team_id')
            ->where('user_id', $this->getKey())
            ->whereIn('role', TeamRoleEnum::rolesAtLeast($teamRoleEnum));
    }

    public function scopeSharesTeamWithActorAtLeast(
        EloquentBuilder $eloquentBuilder,
        Model|Authenticatable $actor,
        TeamRoleEnum $teamRoleEnum
    ): EloquentBuilder {
        $eligibleTeamIds = $actor->myTeamIdsAtLeast($teamRoleEnum);

        return $eloquentBuilder->whereHas('teams', fn ($t) => $t->whereIn('teams.id', $eligibleTeamIds));
    }

    protected static function rolesAtLeast(TeamRoleEnum $teamRoleEnum): array
    {
        $minRank = self::roleRank($teamRoleEnum);

        return array_map(
            static fn (TeamRoleEnum $teamRoleEnum) => $teamRoleEnum->value,
            array_filter(TeamRoleEnum::cases(), static fn (TeamRoleEnum $teamRoleEnum): bool => self::roleRank($teamRoleEnum) >= $minRank)
        );
    }

    protected static function roleRank(TeamRoleEnum $teamRoleEnum): int
    {
        return match ($teamRoleEnum) {
            TeamRoleEnum::VIEWER => 1,
            TeamRoleEnum::MEMBER => 2,
            TeamRoleEnum::ADMIN  => 3,
            TeamRoleEnum::OWNER  => 4,
        };
    }

    public function hasTeamRoleAtLeast(Model $model, TeamRoleEnum|string $min): bool
    {
        $current = $this->teamRole($model);

        if ($current === null) {
            return false;
        }

        $minEnum = $min instanceof TeamRoleEnum ? $min : TeamRoleEnum::from($min);

        return $current->atLeast($minEnum);
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
