<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\Workout;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\Workout\GetWorkoutDetailsDataInput;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseDataModel;
use App\Domain\DTO\DataOutput\Player\Training\Exercise\ExerciseDetailsDataOutput;
use App\Domain\DTO\DataOutput\Player\Training\Exercise\ExerciseMovementDataOutput;
use App\Domain\DTO\DataOutput\Player\Training\ExerciseSet\ExerciseSetDataOutput;
use App\Domain\DTO\DataOutput\Player\Training\Workout\WorkoutDetailsDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Provider\Training\Workout\WorkoutProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\UseCase\AbstractLoggedPlayerUseCase;

final class GetWorkoutDetailsUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly WorkoutProviderGateway $workoutProvider,
    ) {
    }

    /**
     * @param GetWorkoutDetailsDataInput $input
     */
    public function execute(DataInputInterface $input): WorkoutDetailsDataOutput
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $workout = $this->workoutProvider->findOneByIdForDetails($input->id, $player);
        if (null === $workout) {
            throw new EntityNotFoundException(sprintf('Workout "%s" not found.', $input->id));
        }

        $exercises = [];
        foreach ($workout->exercises as $exercise) {
            $exercises[] = self::buildExerciseOutput($exercise);
        }

        return new WorkoutDetailsDataOutput(
            $workout->id,
            $workout->name,
            $workout->status,
            $workout->plannedAt?->format(\DateTimeInterface::ATOM),
            $workout->dateStart?->format(\DateTimeInterface::ATOM),
            $workout->dateEnd?->format(\DateTimeInterface::ATOM),
            $exercises,
            $workout->duration,
            $workout->volume,
            $workout->distance,
            $workout->inclineMeters,
        );
    }

    private static function buildExerciseOutput(ExerciseDataModel $exercise): ExerciseDetailsDataOutput
    {
        $movement = $exercise->movement;
        $sets = [];
        foreach ($exercise->exerciseSets as $set) {
            $sets[] = new ExerciseSetDataOutput(
                $set->id,
                $exercise->id,
                $set->position,
                $set->plannedReps,
                $set->achievedReps,
                $set->plannedWeight,
                $set->achievedWeight,
                $set->plannedDurationSeconds,
                $set->achievedDurationSeconds,
                $set->plannedDistanceMeters,
                $set->achievedDistanceMeters,
                $set->plannedInclinePercent,
                $set->achievedInclinePercent,
                $set->plannedInclineMeters,
                $set->achievedInclineMeters,
                $set->isComplete,
            );
        }

        return new ExerciseDetailsDataOutput(
            $exercise->id,
            $exercise->position,
            $exercise->restDurationSeconds,
            new ExerciseMovementDataOutput(
                $movement->id,
                $movement->slug,
                $movement->label,
                $movement->tracksRepetitions,
                $movement->tracksWeight,
                $movement->tracksDuration,
                $movement->tracksDistance,
                $movement->tracksInclinePercent,
                $movement->tracksInclineMeters,
            ),
            $sets,
        );
    }
}
