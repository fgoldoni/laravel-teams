<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Models;

use Core\Traits\HasAvatarUrl;
use Goldoni\LaravelTeams\Concerns\HasUlidConcerns;
use Goldoni\LaravelTeams\Database\Factories\TeamFactory;
use Goldoni\LaravelTeams\Support\ResolveModel;
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
    use HasUlidConcerns;
    use HasAvatarUrl;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'name'   => 'string',
            'online' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(ResolveModel::user(), 'owner_id');
    }

    protected static function newFactory(): TeamFactory
    {
        return TeamFactory::new();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(ResolveModel::user(), 'team_user')
            ->withPivot(['id', 'ulid', 'role'])
            ->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(ResolveModel::teamUser());
    }
}
