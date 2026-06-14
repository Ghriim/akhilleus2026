<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Player\Profile;

use App\Domain\DTO\DataInput\DataInputInterface;

/**
 * The profile is resolved from the logged player, so the read carries no parameters.
 */
final readonly class GetPlayerProfileDataInput implements DataInputInterface
{
}
