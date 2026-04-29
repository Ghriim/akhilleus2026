<?php

declare(strict_types=1);

namespace App\Infrastructure\DataFixtures;

use App\Domain\DTO\DataModel\Equipment\EquipmentDataModel;
use App\Domain\DTO\DataModel\Movement\MovementDataModel;
use App\Domain\DTO\DataModel\Muscle\MuscleDataModel;
use App\Domain\Gateway\Persister\MovementPersisterGateway;
use App\Domain\Registry\Movement\MovementTrackingFieldRegistry;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Ulid;

final class MovementFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * @var list<array{
     *     label: string,
     *     mainMuscle: string,
     *     secondaryMuscles: list<string>,
     *     equipments: list<string>,
     *     tracks: list<string>,
     * }>
     */
    private const array MOVEMENTS = [
        [
            'label' => 'Back squat',
            'mainMuscle' => 'quadriceps',
            'secondaryMuscles' => ['glutes', 'hamstrings', 'lower-back'],
            'equipments' => ['barbell'],
            'tracks' => [MovementTrackingFieldRegistry::REPETITIONS, MovementTrackingFieldRegistry::WEIGHT],
        ],
        [
            'label' => 'Bench press',
            'mainMuscle' => 'chest',
            'secondaryMuscles' => ['triceps', 'shoulders'],
            'equipments' => ['barbell', 'dumbbell'],
            'tracks' => [MovementTrackingFieldRegistry::REPETITIONS, MovementTrackingFieldRegistry::WEIGHT],
        ],
        [
            'label' => 'Bicep curl',
            'mainMuscle' => 'biceps',
            'secondaryMuscles' => ['forearms'],
            'equipments' => ['dumbbell', 'barbell', 'kettlebell'],
            'tracks' => [MovementTrackingFieldRegistry::REPETITIONS, MovementTrackingFieldRegistry::WEIGHT],
        ],
        [
            'label' => 'Deadlift',
            'mainMuscle' => 'lower-back',
            'secondaryMuscles' => ['hamstrings', 'glutes', 'traps', 'forearms'],
            'equipments' => ['barbell'],
            'tracks' => [MovementTrackingFieldRegistry::REPETITIONS, MovementTrackingFieldRegistry::WEIGHT],
        ],
        [
            'label' => 'Plank',
            'mainMuscle' => 'abdominal',
            'secondaryMuscles' => ['full-body'],
            'equipments' => ['bodyweight'],
            'tracks' => [MovementTrackingFieldRegistry::DURATION],
        ],
        [
            'label' => 'Pull-up',
            'mainMuscle' => 'lats',
            'secondaryMuscles' => ['biceps', 'upper-back'],
            'equipments' => ['bodyweight'],
            'tracks' => [MovementTrackingFieldRegistry::REPETITIONS, MovementTrackingFieldRegistry::WEIGHT],
        ],
        [
            'label' => 'Rowing',
            'mainMuscle' => 'full-body',
            'secondaryMuscles' => ['lats', 'lower-back', 'biceps'],
            'equipments' => ['rower'],
            'tracks' => [MovementTrackingFieldRegistry::DURATION, MovementTrackingFieldRegistry::DISTANCE],
        ],
        [
            'label' => 'Stationary cycling',
            'mainMuscle' => 'cardio',
            'secondaryMuscles' => ['quadriceps', 'glutes', 'calves'],
            'equipments' => ['bike'],
            'tracks' => [MovementTrackingFieldRegistry::DURATION, MovementTrackingFieldRegistry::DISTANCE, MovementTrackingFieldRegistry::INCLINE_PERCENT],
        ],
        [
            'label' => 'Trail run',
            'mainMuscle' => 'cardio',
            'secondaryMuscles' => ['quadriceps', 'calves', 'glutes'],
            'equipments' => ['bodyweight'],
            'tracks' => [MovementTrackingFieldRegistry::DURATION, MovementTrackingFieldRegistry::DISTANCE, MovementTrackingFieldRegistry::INCLINE_PERCENT, MovementTrackingFieldRegistry::INCLINE_METERS],
        ],
        [
            'label' => 'Treadmill running',
            'mainMuscle' => 'cardio',
            'secondaryMuscles' => ['quadriceps', 'calves'],
            'equipments' => ['treadmill'],
            'tracks' => [MovementTrackingFieldRegistry::DURATION, MovementTrackingFieldRegistry::DISTANCE, MovementTrackingFieldRegistry::INCLINE_PERCENT],
        ],
    ];

    public function __construct(
        private readonly MovementPersisterGateway $persister,
    ) {
    }

    public function getDependencies(): array
    {
        return [
            MuscleFixtures::class,
            EquipmentFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::MOVEMENTS as $spec) {
            $mainMuscle = $this->getReference(
                MuscleFixtures::REFERENCE_PREFIX.$spec['mainMuscle'],
                MuscleDataModel::class,
            );

            $movement = new MovementDataModel(
                (string) new Ulid(),
                $spec['label'],
                $mainMuscle,
            );

            foreach ($spec['secondaryMuscles'] as $muscleSlug) {
                $movement->secondaryMuscles->add(
                    $this->getReference(
                        MuscleFixtures::REFERENCE_PREFIX.$muscleSlug,
                        MuscleDataModel::class,
                    ),
                );
            }

            foreach ($spec['equipments'] as $equipmentSlug) {
                $movement->equipments->add(
                    $this->getReference(
                        EquipmentFixtures::REFERENCE_PREFIX.$equipmentSlug,
                        EquipmentDataModel::class,
                    ),
                );
            }

            foreach ($spec['tracks'] as $field) {
                match ($field) {
                    MovementTrackingFieldRegistry::REPETITIONS => $movement->tracksRepetitions = true,
                    MovementTrackingFieldRegistry::WEIGHT => $movement->tracksWeight = true,
                    MovementTrackingFieldRegistry::DURATION => $movement->tracksDuration = true,
                    MovementTrackingFieldRegistry::DISTANCE => $movement->tracksDistance = true,
                    MovementTrackingFieldRegistry::INCLINE_PERCENT => $movement->tracksInclinePercent = true,
                    MovementTrackingFieldRegistry::INCLINE_METERS => $movement->tracksInclineMeters = true,
                };
            }

            $this->persister->create($movement);
        }
    }
}
