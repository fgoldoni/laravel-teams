<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Enums;

enum TeamRoleEnum: string
{
    case OWNER  = 'OWNER';
    case ADMIN  = 'ADMIN';
    case MEMBER = 'MEMBER';
    case VIEWER = 'VIEWER';
}
