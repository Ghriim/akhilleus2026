<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\User;

use App\Domain\DTO\DataOutput\DataOutputInterface;

final readonly class RegisterPlayerDataOutput implements DataOutputInterface
{
    public function __construct(
        public string $playerId,
        public string $email,
        public string $displayName,
    ) {
    }
}
