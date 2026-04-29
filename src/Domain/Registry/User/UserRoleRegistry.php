<?php

declare(strict_types=1);

namespace App\Domain\Registry\User;

interface UserRoleRegistry
{
    public const string ROLE_PLAYER = 'ROLE_PLAYER';
    public const string ROLE_ADMIN = 'ROLE_ADMIN';

    /** @var list<string> */
    public const array ALL = [
        self::ROLE_PLAYER,
        self::ROLE_ADMIN,
    ];
}
