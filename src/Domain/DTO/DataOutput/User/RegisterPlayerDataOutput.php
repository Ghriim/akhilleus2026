<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\User;

use App\Domain\DTO\DataOutput\DataOutputInterface;
use Symfony\Component\ObjectMapper\Attribute\Map;

final class RegisterPlayerDataOutput implements DataOutputInterface
{
    #[Map(source: 'id')]
    public string $playerId;
    #[Map(source: 'user.email')]
    public string $email;
    public string $displayName;
}
