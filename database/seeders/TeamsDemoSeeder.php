<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Database\Seeders;

use Goldoni\LaravelTeams\Actions\AddTeamMember;
use Goldoni\LaravelTeams\Actions\CreateTeam;
use Illuminate\Database\Seeder;

class TeamsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $userModel = config('auth.providers.users.model');

        $owner  = $userModel::factory()->create();
        $member = $userModel::factory()->create();

        $team = app(CreateTeam::class)->handle($owner, 'Demo Team');

        app(AddTeamMember::class)->handle($team, $member);
    }
}
