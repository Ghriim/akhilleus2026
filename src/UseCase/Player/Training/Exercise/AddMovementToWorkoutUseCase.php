<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\Exercise;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\Exercise\AddMovementToWorkoutDataInput;
use App\Domain\DTO\DataModel\Training\Workout\ExerciseDataModel;
use App\Domain\DTO\DataOutput\Player\Training\Exercise\ExerciseDataOutput;
use App\Domain\DTO\DataOutput\Player\Training\Exercise\ExerciseMovementDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Training\Workout\ExercisePersisterGateway;
use App\Domain\Gateway\Provider\Training\Movement\MovementProviderGateway;
use App\Domain\Gateway\Provider\Training\Workout\WorkoutProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Exercise\AddMovementToWorkoutValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class AddMovementToWorkoutUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly AddMovementToWorkoutValidator $addMovementToWorkoutValidator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly WorkoutProviderGateway $workoutProvider,
        private readonly MovementProviderGateway $movementProvider,
        private readonly ExercisePersisterGateway $exercisePersister,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param AddMovementToWorkoutDataInput $input
     */
    public function execute(DataInputInterface $input): ExerciseDataOutput
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $workout = $this->workoutProvider->findOneByIdForPlayerAction($input->workoutId, $player);
        if (null === $workout) {
            throw new EntityNotFoundException(sprintf('Workout "%s" not found.', $input->workoutId));
        }

        $this->addMovementToWorkoutValidator->validate($player, $input, $workout);

        $movement = $this->movementProvider->findOneByIdForExerciseAttachment($input->movementId);
        if (null === $movement) {
            throw new EntityNotFoundException(sprintf('Movement "%s" not found.', $input->movementId));
        }

        $nextPosition = 0;
        foreach ($workout->exercises as $exercise) {
            if ($exercise->position >= $nextPosition) {
                $nextPosition = $exercise->position + 1;
            }
        }

        $created = $this->exercisePersister->create(new ExerciseDataModel(
            $workout,
            $movement,
            $nextPosition,
            $input->restDurationSeconds,
        ));
        $workout->exercises->add($created);

        return new ExerciseDataOutput(
            $created->id,
            $workout->id,
            $created->position,
            $created->restDurationSeconds,
            $this->mapper->map($movement, ExerciseMovementDataOutput::class),
        );
    }
}
