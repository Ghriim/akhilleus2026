<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\Exercise;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\Exercise\UpdateMovementRestDurationDataInput;
use App\Domain\DTO\DataOutput\Player\Training\Exercise\ExerciseDataOutput;
use App\Domain\DTO\DataOutput\Player\Training\Exercise\ExerciseMovementDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Training\Workout\ExercisePersisterGateway;
use App\Domain\Gateway\Provider\Training\Workout\ExerciseProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Exercise\UpdateMovementRestDurationValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class UpdateMovementRestDurationUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly UpdateMovementRestDurationValidator $updateMovementRestDurationValidator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly ExerciseProviderGateway $exerciseProvider,
        private readonly ExercisePersisterGateway $exercisePersister,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param UpdateMovementRestDurationDataInput $input
     */
    public function execute(DataInputInterface $input): ExerciseDataOutput
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $exercise = $this->exerciseProvider->findOneByIdForPlayerAction($input->exerciseId, $player);
        if (null === $exercise) {
            throw new EntityNotFoundException(sprintf('Exercise "%s" not found.', $input->exerciseId));
        }

        $this->updateMovementRestDurationValidator->validate($player, $input, $exercise);

        $exercise->restDurationSeconds = $input->restDurationSeconds;
        $this->exercisePersister->update($exercise);

        return new ExerciseDataOutput(
            $exercise->id,
            $exercise->workout->id,
            $exercise->position,
            $exercise->restDurationSeconds,
            $this->mapper->map($exercise->movement, ExerciseMovementDataOutput::class),
        );
    }
}
