<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Database\Factories;

use Goldoni\LaravelTeams\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        $userModel = config('auth.providers.users.model');

        return [
            'name'     => $this->faker->company(),
            'owner_id' => $userModel::factory(),
            'ulid'     => (string) Str::ulid(),
        ];
    }
}
