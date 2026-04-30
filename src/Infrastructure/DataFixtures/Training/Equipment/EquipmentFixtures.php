<?php

declare(strict_types=1);

namespace App\Infrastructure\DataFixtures\Training\Equipment;

use App\Domain\DTO\DataModel\Training\Equipment\EquipmentDataModel;
use App\Domain\Gateway\Persister\Training\Equipment\EquipmentPersisterGateway;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class EquipmentFixtures extends Fixture
{
    public const string REFERENCE_PREFIX = 'equipment-';

    /** @var list<string> */
    private const array EQUIPMENTS = [
        'Barbell',
        'Bike',
        'Bodyweight',
        'Dumbbell',
        'Kettlebell',
        'Machine',
        'Rower',
        'Treadmill',
    ];

    public function __construct(
        private readonly EquipmentPersisterGateway $persister,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::EQUIPMENTS as $label) {
            $equipment = new EquipmentDataModel($label);
            $this->persister->create($equipment);
            $this->addReference(self::REFERENCE_PREFIX.$equipment->slug, $equipment);
        }
    }
}
