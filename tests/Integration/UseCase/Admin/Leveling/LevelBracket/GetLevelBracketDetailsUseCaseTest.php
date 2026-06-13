<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Leveling\LevelBracket;

use App\Domain\DTO\DataInput\Admin\Leveling\LevelBracket\GetLevelBracketDetailsDataInput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Provider\Leveling\LevelBracket\LevelBracketProviderGateway;
use App\UseCase\Admin\Leveling\LevelBracket\GetLevelBracketDetailsUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class GetLevelBracketDetailsUseCaseTest extends KernelTestCase
{
    public function testItReturnsTheBracketDetails(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $first = $container->get(LevelBracketProviderGateway::class)->findAllOrderedAsc()[0];

        $output = $container->get(GetLevelBracketDetailsUseCase::class)->execute(new GetLevelBracketDetailsDataInput($first->id));

        self::assertSame($first->id, $output->id);
        self::assertSame(1, $output->fromLevel);
        self::assertSame(10, $output->toLevel);
    }

    public function testItThrowsNotFoundForAnUnknownId(): void
    {
        self::bootKernel();

        $this->expectException(EntityNotFoundException::class);

        self::getContainer()->get(GetLevelBracketDetailsUseCase::class)->execute(
            new GetLevelBracketDetailsDataInput('00000000000000000000000000'),
        );
    }
}
