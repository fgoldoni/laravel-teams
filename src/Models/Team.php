<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Models;

use Goldoni\LaravelTeams\Concerns\HasExtraUlid;
use Goldoni\LaravelTeams\Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use SoftDeletes;
    use HasFactory;
    use HasExtraUlid;

    protected $table = 'teams';

    protected $fillable = [
        'ulid',
        'name',
        'owner_id',
    ];

    protected function casts(): array
    {
        return [
            'ulid' => 'string',
            'name' => 'string',
            'online' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'owner_id');
    }

    protected static function newFactory(): TeamFactory
    {
        return TeamFactory::new();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(config('auth.providers.users.model'), 'team_user')
            ->withPivot(['id', 'ulid', 'role'])
            ->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(TeamUser::class);
    }
}
