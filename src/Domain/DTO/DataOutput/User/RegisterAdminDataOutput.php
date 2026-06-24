<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataOutput\User;

use App\Domain\DTO\DataOutput\DataOutputInterface;
use Symfony\Component\ObjectMapper\Attribute\Map;

final class RegisterAdminDataOutput implements DataOutputInterface
{
    public string $id;
    #[Map(source: 'user.email')]
    public string $email;
}
