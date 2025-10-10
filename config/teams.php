<?php

declare(strict_types=1);

use Goldoni\LaravelTeams\Models\Team;
use Goldoni\LaravelTeams\Models\TeamUser;

return [
    'roles' => [
        'OWNER'  => 'Owner',
        'ADMIN'  => 'Admin',
        'MEMBER' => 'Member',
        'VIEWER' => 'Viewer',
    ],
    'default_role'             => 'MEMBER',
    'max_teams_per_user'       => 0,
    'invite_notifications'     => true,
    'super_admin_role'         => 'Super Admin',
    'observe_current_team'     => true,
    'models'                   => [
        'team'      => Team::class,
        'team_user' => TeamUser::class,
        'user'      => null,
    ],
];
