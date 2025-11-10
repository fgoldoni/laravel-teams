<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Concerns;

use Illuminate\Support\Str;

trait HasUlidConcerns
{
    protected static function bootHasUlidConcerns(): void
    {
        static::creating(function ($model): void {
            if (empty($model->ulid)) {
                $model->ulid = (string) Str::ulid();
            }
        });
    }
}
