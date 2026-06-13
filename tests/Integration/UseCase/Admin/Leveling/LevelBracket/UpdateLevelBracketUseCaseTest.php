<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Leveling\LevelBracket;

use App\Domain\DTO\DataInput\Admin\Leveling\LevelBracket\UpdateLevelBracketDataInput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Leveling\LevelBracket\LevelBracketProviderGateway;
use App\Domain\Validator\Admin\Leveling\LevelBracket\UpdateLevelBracketValidator;
use App\UseCase\Admin\Leveling\LevelBracket\UpdateLevelBracketUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UpdateLevelBracketUseCaseTest extends KernelTestCase
{
    public function testItUpdatesABracketAndReturnsTheOutput(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        // Seeded curve: [1-10, 11-20, 21-∞]. Re-tune the middle bracket's coefficient only.
        $middle = $container->get(LevelBracketProviderGateway::class)->findAllOrderedAsc()[1];

        $output = $container->get(UpdateLevelBracketUseCase::class)->execute(
            new UpdateLevelBracketDataInput($middle->id, $middle->fromLevel, $middle->toLevel, 9000, $middle->exponentK, $middle->offsetB),
        );

        self::assertSame($middle->id, $output->id);
        self::assertSame(9000, $output->coefficientA);
        self::assertSame(11, $output->fromLevel);
    }

    public function testItRejectsAnUpdateThatBreaksTheCurve(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $first = $container->get(LevelBracketProviderGateway::class)->findAllOrderedAsc()[0];

        try {
            // Move the first bracket off level 1 → curve no longer starts at 1.
            $container->get(UpdateLevelBracketUseCase::class)->execute(
                new UpdateLevelBracketDataInput($first->id, 5, 10, 1000, 2, 0),
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(UpdateLevelBracketValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('curve', $e->violations);
        }
    }

    public function testItThrowsNotFoundForAnUnknownId(): void
    {
        self::bootKernel();

        $this->expectException(EntityNotFoundException::class);

        self::getContainer()->get(UpdateLevelBracketUseCase::class)->execute(
            new UpdateLevelBracketDataInput('00000000000000000000000000', 1, null, 1000, 2, 0),
        );
    }
}
