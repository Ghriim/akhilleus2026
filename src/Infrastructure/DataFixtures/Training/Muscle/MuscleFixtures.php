<?php

declare(strict_types=1);

namespace App\Infrastructure\DataFixtures\Training\Muscle;

use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;
use App\Domain\Gateway\Persister\Training\Muscle\MusclePersisterGateway;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class MuscleFixtures extends Fixture
{
    public const string REFERENCE_PREFIX = 'muscle-';

    /** @var list<string> */
    private const array MUSCLES = [
        'Abdominal',
        'Abductors',
        'Adductors',
        'Biceps',
        'Calves',
        'Cardio',
        'Chest',
        'Forearms',
        'Full body',
        'Glutes',
        'Hamstrings',
        'Lats',
        'Lower back',
        'Neck',
        'Other',
        'Quadriceps',
        'Shoulders',
        'Traps',
        'Triceps',
        'Upper back',
    ];

    public function __construct(
        private readonly MusclePersisterGateway $persister,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::MUSCLES as $label) {
            $muscle = new MuscleDataModel($label);
            $this->persister->create($muscle);
            $this->addReference(self::REFERENCE_PREFIX.$muscle->slug, $muscle);
        }
    }
}
