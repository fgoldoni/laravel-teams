<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Models;

use Core\Traits\BelongsToTeam;
use Core\Traits\BelongsToUser;
use Goldoni\LaravelTeams\Concerns\HasUlidConcerns;
use Goldoni\LaravelTeams\Database\Factories\TeamUserFactory;
use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Goldoni\LaravelTeams\Support\ResolveModel;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeamUser extends Model
{
    use HasFactory;
    use HasUlidConcerns;
    use SoftDeletes;
    use BelongsToUser;
    use BelongsToTeam;

    protected $table = 'team_user';

    protected $fillable = [
        'ulid',
        'team_id',
        'user_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'role' => TeamRoleEnum::class,
        ];
    }

    protected static function newFactory(): TeamUserFactory
    {
        return TeamUserFactory::new();
    }

    public function isOwner(): bool
    {
        return $this->role === TeamRoleEnum::OWNER;
    }

    public function isAdmin(): bool
    {
        return $this->role === TeamRoleEnum::ADMIN;
    }

    public function isMember(): bool
    {
        return $this->role === TeamRoleEnum::MEMBER;
    }

    public function isViewer(): bool
    {
        return $this->role === TeamRoleEnum::VIEWER;
    }

    #[Scope]
    public function forUser(Builder $builder, int $userId): Builder
    {
        return $builder->where('user_id', $userId);
    }
}
