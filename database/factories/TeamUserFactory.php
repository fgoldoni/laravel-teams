<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Database\Factories;

use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Goldoni\LaravelTeams\Models\TeamUser;
use Goldoni\LaravelTeams\Support\ResolveModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamUserFactory extends Factory
{
    protected $model = TeamUser::class;

    public function definition(): array
    {
        config('auth.providers.users.model');

        return [
            'team_id' => (ResolveModel::team())::factory(),
            'user_id' => (ResolveModel::user())::factory(),
            'role'    => $this->faker->randomElement([
                TeamRoleEnum::ADMIN->value,
                TeamRoleEnum::MEMBER->value,
                TeamRoleEnum::VIEWER->value,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
