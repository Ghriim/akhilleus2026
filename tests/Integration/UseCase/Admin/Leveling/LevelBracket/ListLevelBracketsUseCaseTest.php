<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Leveling\LevelBracket;

use App\Domain\DTO\DataInput\Admin\Leveling\LevelBracket\ListLevelBracketsDataInput;
use App\UseCase\Admin\Leveling\LevelBracket\ListLevelBracketsUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListLevelBracketsUseCaseTest extends KernelTestCase
{
    public function testItReturnsTheSeededCurveOrderedByFromLevel(): void
    {
        self::bootKernel();

        $output = self::getContainer()->get(ListLevelBracketsUseCase::class)->execute(new ListLevelBracketsDataInput());

        self::assertCount(3, $output);
        self::assertSame([1, 11, 21], array_map(static fn ($item) => $item->fromLevel, $output));
        self::assertSame([10, 20, null], array_map(static fn ($item) => $item->toLevel, $output));
    }
}
