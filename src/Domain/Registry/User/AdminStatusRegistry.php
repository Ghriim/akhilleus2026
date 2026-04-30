<?php

declare(strict_types=1);

namespace App\Domain\Registry\User;

interface AdminStatusRegistry
{
    public const string ACTIVE = 'ACTIVE';
    public const string INACTIVE = 'INACTIVE';

    /** @var list<string> */
    public const array ALL = [
        self::ACTIVE,
        self::INACTIVE,
    ];
}
