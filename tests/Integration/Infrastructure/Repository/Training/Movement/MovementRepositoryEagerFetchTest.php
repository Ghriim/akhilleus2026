<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Repository\Training\Movement;

use App\Domain\DTO\DataModel\Training\Equipment\EquipmentDataModel;
use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;
use App\Domain\Gateway\Persister\Training\Equipment\EquipmentPersisterGateway;
use App\Domain\Gateway\Persister\Training\Movement\MovementPersisterGateway;
use App\Domain\Gateway\Persister\Training\Muscle\MusclePersisterGateway;
use App\Domain\Gateway\Provider\Training\Movement\MovementProviderGateway;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MovementRepositoryEagerFetchTest extends KernelTestCase
{
    public function testFindOneForAdminDetailsEagerlyHydratesEveryAssociation(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $musclePersister = $container->get(MusclePersisterGateway::class);
        $equipmentPersister = $container->get(EquipmentPersisterGateway::class);
        $movementPersister = $container->get(MovementPersisterGateway::class);
        $repository = $container->get(MovementProviderGateway::class);
        $entityManager = $container->get(EntityManagerInterface::class);

        $main = $musclePersister->create(new MuscleDataModel('Eager-test main'));
        $secondary1 = $musclePersister->create(new MuscleDataModel('Eager-test secondary 1'));
        $secondary2 = $musclePersister->create(new MuscleDataModel('Eager-test secondary 2'));

        $eq1 = $equipmentPersister->create(new EquipmentDataModel('Eager-test equipment 1'));
        $eq2 = $equipmentPersister->create(new EquipmentDataModel('Eager-test equipment 2'));

        $movement = new MovementDataModel('Eager-test movement', $main);
        $movement->secondaryMuscles->add($secondary1);
        $movement->secondaryMuscles->add($secondary2);
        $movement->equipments->add($eq1);
        $movement->equipments->add($eq2);
        $movement->tracksRepetitions = true;
        $movement->tracksWeight = true;
        $movementPersister->create($movement);

        $movementId = $movement->id;

        $entityManager->clear();

        $loaded = $repository->findOneForAdminDetails($movementId);

        self::assertInstanceOf(MovementDataModel::class, $loaded);
        self::assertSame($movementId, $loaded->id);

        self::assertFalse(
            $entityManager->getUnitOfWork()->isUninitializedObject($loaded->mainMuscle),
            'mainMuscle must be eager-loaded (not a lazy proxy)',
        );
        self::assertSame('eager-test-main', $loaded->mainMuscle->slug);

        self::assertInstanceOf(PersistentCollection::class, $loaded->secondaryMuscles);
        self::assertTrue(
            $loaded->secondaryMuscles->isInitialized(),
            'secondaryMuscles must be eager-loaded (PersistentCollection initialized)',
        );
        self::assertCount(2, $loaded->secondaryMuscles);

        self::assertInstanceOf(PersistentCollection::class, $loaded->equipments);
        self::assertTrue(
            $loaded->equipments->isInitialized(),
            'equipments must be eager-loaded (PersistentCollection initialized)',
        );
        self::assertCount(2, $loaded->equipments);
    }
}
