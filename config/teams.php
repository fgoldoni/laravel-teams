<?php

declare(strict_types=1);

return [
    'roles' => [
        'OWNER'  => 'Owner',
        'ADMIN'  => 'Admin',
        'MEMBER' => 'Member',
        'VIEWER' => 'Viewer',
    ],
    'default_role'         => 'MEMBER',
    'max_teams_per_user'   => 0,
    'invite_notifications' => true,
    'super_admin_role'     => 'Super Admin',
];
