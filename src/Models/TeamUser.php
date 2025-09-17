<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Models;

use Goldoni\LaravelTeams\Concerns\HasExtraUlid;
use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamUser extends Model
{
    use HasFactory;
    use HasExtraUlid;

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
            'ulid' => 'string',
            'role' => 'string',
        ];
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
        return $this->role === TeamRoleEnum::OWNER->value;
    }

    public function isAdmin(): bool
    {
        return $this->role === TeamRoleEnum::ADMIN->value;
    }

    public function isMember(): bool
    {
        return $this->role === TeamRoleEnum::MEMBER->value;
    }

    public function isViewer(): bool
    {
        return $this->role === TeamRoleEnum::VIEWER->value;
    }
}
