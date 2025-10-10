<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Enums;

enum TeamRoleEnum: string
{
    case OWNER  = 'OWNER';
    case ADMIN  = 'ADMIN';
    case MEMBER = 'MEMBER';
    case VIEWER = 'VIEWER';

    public function label(): string
    {
        return match ($this) {
            self::OWNER  => 'Owner',
            self::ADMIN  => 'Admin',
            self::MEMBER => 'Member',
            self::VIEWER => 'Viewer',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $c): array => [$c->value => $c->label()]
        )->all();
    }

    public static function values(): array
    {
        return array_map(static fn (self $c) => $c->value, self::cases());
    }

    public static function labels(): array
    {
        return array_map(static fn (self $c): string => $c->label(), self::cases());
    }

    public static function tryFromValue(?string $value): ?self
    {
        return $value !== null ? self::tryFrom($value) : null;
    }

    public function rank(): int
    {
        return match ($this) {
            self::OWNER  => 4,
            self::ADMIN  => 3,
            self::MEMBER => 2,
            self::VIEWER => 1,
        };
    }

    public function atLeast(self $min): bool
    {
        return $this->rank() >= $min->rank();
    }

    public function atMost(self $max): bool
    {
        return $this->rank() <= $max->rank();
    }

    public static function rolesAtLeast(self $min): array
    {
        return array_map(
            static fn (self $c) => $c->value,
            array_filter(self::cases(), static fn (self $c): bool => $c->atLeast($min))
        );
    }

    public static function rolesAtMost(self $max): array
    {
        return array_map(
            static fn (self $c) => $c->value,
            array_filter(self::cases(), static fn (self $c): bool => $c->atMost($max))
        );
    }

    public function isOwner(): bool
    {
        return $this === self::OWNER;
    }

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    public function isMember(): bool
    {
        return $this === self::MEMBER;
    }

    public function isViewer(): bool
    {
        return $this === self::VIEWER;
    }
}
