<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Player\Training\ExerciseSet;

use App\Domain\DTO\DataInput\User\RegisterPlayerDataInput;
use App\Domain\DTO\DataModel\Training\Movement\MovementDataModel;
use App\Domain\DTO\DataModel\Training\Muscle\MuscleDataModel;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseDataModel;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseSetDataModel;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Gateway\Persister\Training\Movement\MovementPersisterGateway;
use App\Domain\Gateway\Persister\Training\Muscle\MusclePersisterGateway;
use App\Domain\Gateway\Persister\User\PlayerPersisterGateway;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Infrastructure\Persister\Training\Workout\ExercisePersister;
use App\Infrastructure\Persister\Training\Workout\ExerciseSetPersister;
use App\Infrastructure\Persister\Training\Workout\WorkoutPersister;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;

trait ExerciseSetTestSetupTrait
{
    private static function createTestPlayer(ContainerInterface $container, string $emailSlug): PlayerDataModel
    {
        return $container->get(PlayerPersisterGateway::class)->create(new RegisterPlayerDataInput(
            $emailSlug.'@akhilleus.test',
            'StrongPass1!',
            'Workout Hero',
        ));
    }

    /**
     * @param array{tracksRepetitions?: bool, tracksWeight?: bool, tracksDuration?: bool, tracksDistance?: bool, tracksInclinePercent?: bool, tracksInclineMeters?: bool} $tracking
     *
     * @return array{ExerciseDataModel, ExerciseSetDataModel}
     */
    private static function createTestExerciseWithSet(
        ContainerInterface $container,
        PlayerDataModel $player,
        string $status = WorkoutStatusRegistry::IN_PROGRESS,
        array $tracking = ['tracksRepetitions' => true, 'tracksWeight' => true],
    ): array {
        $em = $container->get('doctrine.orm.entity_manager');
        $clock = $container->get(ClockInterface::class);
        $workoutPersister = new WorkoutPersister($em, $clock);
        $exercisePersister = new ExercisePersister($em, $clock);
        $exerciseSetPersister = new ExerciseSetPersister($em, $clock);

        $workout = new WorkoutDataModel($player, $status);
        if (WorkoutStatusRegistry::PLANNED === $status) {
            $workout->plannedAt = $clock->now()->modify('+1 day');
        } else {
            $workout->dateStart = $clock->now();
        }
        if (WorkoutStatusRegistry::COMPLETED === $status) {
            $workout->dateEnd = $clock->now();
        }
        $workoutPersister->create($workout);

        $muscle = $container->get(MusclePersisterGateway::class)->create(new MuscleDataModel('Test muscle '.uniqid('', true)));
        $movement = new MovementDataModel('Test movement '.uniqid('', true), $muscle);
        $movement->tracksRepetitions = $tracking['tracksRepetitions'] ?? false;
        $movement->tracksWeight = $tracking['tracksWeight'] ?? false;
        $movement->tracksDuration = $tracking['tracksDuration'] ?? false;
        $movement->tracksDistance = $tracking['tracksDistance'] ?? false;
        $movement->tracksInclinePercent = $tracking['tracksInclinePercent'] ?? false;
        $movement->tracksInclineMeters = $tracking['tracksInclineMeters'] ?? false;
        $container->get(MovementPersisterGateway::class)->create($movement);

        $exercise = $exercisePersister->create(new ExerciseDataModel($workout, $movement, 0, 60));

        $set = new ExerciseSetDataModel($exercise, 0);
        $set->plannedReps = 10;
        if ($movement->tracksWeight) {
            $set->plannedWeight = '40.00';
        }
        $exerciseSetPersister->create($set);

        return [$exercise, $set];
    }

    private static function stubResolver(PlayerDataModel $player): LoggedPlayerResolverInterface
    {
        return new class ($player) implements LoggedPlayerResolverInterface {
            public function __construct(private PlayerDataModel $player)
            {
            }

            public function getLoggedPlayer(): PlayerDataModel
            {
                return $this->player;
            }
        };
    }
}
