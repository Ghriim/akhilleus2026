<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataInput\Admin\Questing\Quest;

use App\Domain\DTO\DataInput\DataInputInterface;

/**
 * The admin quest list returns every quest (active, expired or not-yet-started), ordered by
 * `dateStart` DESC, so it carries no sort/direction options.
 */
final readonly class ListQuestsDataInput implements DataInputInterface
{
}
