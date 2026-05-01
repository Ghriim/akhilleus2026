<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Training\Movement;

use App\Domain\DTO\DataInput\Player\Training\Movement\ListMovementsForPlayerDataInput;
use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;
use App\Domain\Gateway\Persister\Training\Movement\MovementPersisterGateway;
use App\Domain\Gateway\Persister\Training\Muscle\MusclePersisterGateway;
use App\Infrastructure\Repository\Training\Movement\MovementRepository;
use App\UseCase\Player\Training\Movement\ListMovementsForPlayerUseCase;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ListMovementsForPlayerUseCaseTest extends KernelTestCase
{
    public function testItReturnsAllMovementsWithTrackingFlagsAndMainMuscleSlug(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $muscle = $container->get(MusclePersisterGateway::class)->create(new MuscleDataModel('Test muscle player-mvt-list'));
        $movement = new MovementDataModel('Test bench player', $muscle);
        $movement->tracksRepetitions = true;
        $movement->tracksWeight = true;
        $container->get(MovementPersisterGateway::class)->create($movement);

        $useCase = new ListMovementsForPlayerUseCase(
            new MovementRepository($container->get(ManagerRegistry::class)),
        );

        $output = $useCase->execute(new ListMovementsForPlayerDataInput());

        self::assertNotEmpty($output);
        $found = null;
        foreach ($output as $item) {
            if ($item->id === $movement->id) {
                $found = $item;
                break;
            }
        }
        self::assertNotNull($found);
        self::assertSame($muscle->slug, $found->mainMuscleSlug);
        self::assertTrue($found->tracksRepetitions);
        self::assertTrue($found->tracksWeight);
        self::assertFalse($found->tracksDuration);
    }
}
