<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\Exercise;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\Exercise\ReorderMovementsDataInput;
use App\Domain\DTO\DataOutput\Player\Training\Exercise\ExerciseDataOutput;
use App\Domain\DTO\DataOutput\Player\Training\Exercise\ExerciseMovementDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Training\Workout\ExercisePersisterGateway;
use App\Domain\Gateway\Provider\Training\Workout\ExerciseProviderGateway;
use App\Domain\Gateway\Provider\Training\Workout\WorkoutProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Exercise\ReorderMovementsValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;

final class ReorderMovementsUseCase extends AbstractLoggedPlayerUseCase
{
    public const string MISMATCH_ERROR_CODE = 'REORDER_MOVEMENTS_IDS_MISMATCH';

    public function __construct(
        private readonly ReorderMovementsValidator $reorderMovementsValidator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly WorkoutProviderGateway $workoutProvider,
        private readonly ExerciseProviderGateway $exerciseProvider,
        private readonly ExercisePersisterGateway $exercisePersister,
    ) {
    }

    /**
     * @param ReorderMovementsDataInput $input
     *
     * @return list<ExerciseDataOutput>
     */
    public function execute(DataInputInterface $input): array
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $workout = $this->workoutProvider->findOneByIdForPlayerAction($input->workoutId, $player);
        if (null === $workout) {
            throw new EntityNotFoundException(sprintf('Workout "%s" not found.', $input->workoutId));
        }

        $this->reorderMovementsValidator->validate($player, $input, $workout);

        $existing = $this->exerciseProvider->findAllByWorkoutIdForPlayerAction($workout->id, $player);

        $existingIds = [];
        $exerciseById = [];
        foreach ($existing as $exercise) {
            $existingIds[] = $exercise->id;
            $exerciseById[$exercise->id] = $exercise;
        }
        sort($existingIds);
        $sortedInputIds = $input->orderedExerciseIds;
        sort($sortedInputIds);
        if ($existingIds !== $sortedInputIds) {
            throw new ValidationException('Ordered exercise ids must match exactly the workout exercises.', ['orderedExerciseIds' => ['Ordered ids do not match the workout exercises.']], self::MISMATCH_ERROR_CODE);
        }

        $reordered = [];
        foreach ($input->orderedExerciseIds as $index => $exerciseId) {
            $exercise = $exerciseById[$exerciseId];
            $exercise->position = $index;
            $this->exercisePersister->update($exercise);
            $reordered[] = $exercise;
        }

        return array_map(
            fn ($exercise) => new ExerciseDataOutput(
                $exercise->id,
                $workout->id,
                $exercise->position,
                $exercise->restDurationSeconds,
                new ExerciseMovementDataOutput(
                    $exercise->movement->id,
                    $exercise->movement->slug,
                    $exercise->movement->label,
                    $exercise->movement->tracksRepetitions,
                    $exercise->movement->tracksWeight,
                    $exercise->movement->tracksDuration,
                    $exercise->movement->tracksDistance,
                    $exercise->movement->tracksInclinePercent,
                    $exercise->movement->tracksInclineMeters,
                    $exercise->movement->videoLink,
                    $exercise->movement->gifLink,
                ),
            ),
            $reordered,
        );
    }
}
