<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Database\Factories;

use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Goldoni\LaravelTeams\Models\Team;
use Goldoni\LaravelTeams\Models\TeamUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamUserFactory extends Factory
{
    protected $model = TeamUser::class;

    public function definition(): array
    {
        $userModel = config('auth.providers.users.model');

        return [
            'team_id' => Team::factory(),
            'user_id' => $userModel::factory(),
            'role' => $this->faker->randomElement([
                TeamRoleEnum::ADMIN->value,
                TeamRoleEnum::MEMBER->value,
                TeamRoleEnum::VIEWER->value,
            ]),
        ];
    }
}
