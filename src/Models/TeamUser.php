<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Models;

use Goldoni\LaravelTeams\Concerns\HasExtraUlid;
use Goldoni\LaravelTeams\Database\Factories\TeamUserFactory;
use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeamUser extends Model
{
    use HasFactory;
    use HasExtraUlid;
    use SoftDeletes;

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

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
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
