<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin\Leveling\LevelBracket;

use App\Domain\DTO\DataInput\Admin\Leveling\LevelBracket\CreateLevelBracketDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Leveling\LevelBracket\LevelBracketPersisterGateway;
use App\Domain\Gateway\Provider\Leveling\LevelBracket\LevelBracketProviderGateway;
use App\Domain\Validator\Admin\Leveling\LevelBracket\CreateLevelBracketValidator;
use App\UseCase\Admin\Leveling\LevelBracket\CreateLevelBracketUseCase;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateLevelBracketUseCaseTest extends KernelTestCase
{
    public function testItCreatesABracketAndReturnsTheOutput(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        self::clearBrackets($container);

        $useCase = $container->get(CreateLevelBracketUseCase::class);
        $output = $useCase->execute(new CreateLevelBracketDataInput(1, null, 1000, 2, 0));

        self::assertNotEmpty($output->id);
        self::assertSame(1, $output->fromLevel);
        self::assertNull($output->toLevel);
        self::assertSame(1000, $output->coefficientA);

        self::assertCount(1, $container->get(LevelBracketProviderGateway::class)->findAllOrderedAsc());
    }

    public function testItRejectsACreateThatBreaksTheCurve(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        // Keep the seeded baseline curve (1-10, 11-20, 21-∞); a 5-8 bracket overlaps it.
        $useCase = $container->get(CreateLevelBracketUseCase::class);

        try {
            $useCase->execute(new CreateLevelBracketDataInput(5, 8, 1000, 2, 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(CreateLevelBracketValidator::ERROR_CODE, $e->errorCode);
            self::assertArrayHasKey('curve', $e->violations);
        }
    }

    public function testItRejectsInvalidFields(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        self::clearBrackets($container);

        try {
            $container->get(CreateLevelBracketUseCase::class)
                ->execute(new CreateLevelBracketDataInput(0, 5, 1000, 0, 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('fromLevel', $e->violations);
            self::assertArrayHasKey('exponentK', $e->violations);
        }
    }

    private static function clearBrackets(ContainerInterface $container): void
    {
        $provider = $container->get(LevelBracketProviderGateway::class);
        $persister = $container->get(LevelBracketPersisterGateway::class);
        foreach ($provider->findAllOrderedAsc() as $bracket) {
            $persister->delete($bracket);
        }
    }
}
