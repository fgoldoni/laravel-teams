<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Support;

use App\Models\User;
use Goldoni\LaravelTeams\Models\Team;
use Goldoni\LaravelTeams\Models\TeamUser;
use InvalidArgumentException;

final class ResolveModel
{
    private const DEFAULTS = [
        'team'      => Team::class,
        'team_user' => TeamUser::class,
    ];

    public static function resolve(string $key): string
    {
        $default = self::DEFAULTS[$key] ?? (config('auth.providers.users.model') ?? User::class);
        $value   = config('teams.models.' . $key) ?: $default;

        if (! is_string($value) || ! \class_exists($value)) {
            throw new InvalidArgumentException('Invalid model class for teams.models.' . $key);
        }

        return $value;
    }

    public static function team(): string
    {
        return self::resolve('team');
    }

    public static function teamUser(): string
    {
        return self::resolve('team_user');
    }

    public static function user(): string
    {
        return self::resolve('user');
    }
}
