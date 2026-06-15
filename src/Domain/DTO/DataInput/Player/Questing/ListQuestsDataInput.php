<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Player\Questing;

use App\Domain\DTO\DataInput\DataInputInterface;

/**
 * Shared empty input for the periodicity-scoped quest listings (daily / weekly / monthly / unique):
 * the periodicity is fixed per use case and the player is resolved from the security context, so no
 * parameters are carried.
 */
final readonly class ListQuestsDataInput implements DataInputInterface
{
}
