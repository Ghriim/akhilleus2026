<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\User;

use App\Domain\DTO\DataInput\DataInputInterface;

final readonly class RegisterPlayerDataInput implements DataInputInterface
{
    public function __construct(
        public string $email,
        public string $plainPassword,
        public string $displayName,
    ) {
    }
}
