<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Leveling\LevelBracket;

use App\Domain\DTO\DataInput\Admin\Leveling\LevelBracket\DeleteLevelBracketDataInput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Provider\Leveling\LevelBracket\LevelBracketProviderGateway;
use App\UseCase\Admin\Leveling\LevelBracket\DeleteLevelBracketUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DeleteLevelBracketUseCaseTest extends KernelTestCase
{
    public function testItDeletesABracketAndReturnsTheDeletedId(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $provider = $container->get(LevelBracketProviderGateway::class);
        // Delete the open-ended tail of the seeded curve.
        $last = $provider->findAllOrderedAsc()[2];

        $output = $container->get(DeleteLevelBracketUseCase::class)->execute(new DeleteLevelBracketDataInput($last->id));

        self::assertSame($last->id, $output->deletedId);
        self::assertNull($provider->findOneByIdForAdminAction($last->id));
    }

    public function testItThrowsNotFoundForAnUnknownId(): void
    {
        self::bootKernel();

        $this->expectException(EntityNotFoundException::class);

        self::getContainer()->get(DeleteLevelBracketUseCase::class)->execute(
            new DeleteLevelBracketDataInput('00000000000000000000000000'),
        );
    }
}
