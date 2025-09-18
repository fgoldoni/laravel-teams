<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Providers;

use Goldoni\LaravelTeams\Contracts\TeamsManager as TeamsManagerContract;
use Goldoni\LaravelTeams\Models\Team;
use Goldoni\LaravelTeams\Policies\TeamPolicy;
use Goldoni\LaravelTeams\Services\TeamsManager;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class TeamsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/teams.php', 'teams');

        $this->app->singleton(TeamsManagerContract::class, TeamsManager::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/teams.php' => config_path('teams.php'),
        ], 'teams-config');

        $this->publishes([
            __DIR__ . '/../../database/migrations/2025_09_18_025705_add_current_team_id_to_users_table.php' =>
                database_path('migrations/2025_09_18_025705_add_current_team_id_to_users_table.php'),
        ], 'teams-migrations');

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        Gate::policy(Team::class, TeamPolicy::class);

        Gate::before(function ($user, $ability): ?true {
            $role = config('teams.super_admin_role');

            if ($role && method_exists($user, 'hasRole') && $user->hasRole($role)) {
                return true;
            }

            return null;
        });
    }
}
