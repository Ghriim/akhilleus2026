<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\Workout;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\Workout\CancelWorkoutDataInput;
use App\Domain\DTO\DataOutput\Player\Training\Workout\WorkoutDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Training\Workout\WorkoutPersisterGateway;
use App\Domain\Gateway\Provider\Training\Workout\WorkoutProviderGateway;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Workout\CancelWorkoutValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class CancelWorkoutUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly CancelWorkoutValidator $cancelWorkoutValidator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly WorkoutProviderGateway $workoutProvider,
        private readonly WorkoutPersisterGateway $workoutPersister,
        private readonly ObjectMapperInterface $mapper,
    ) {
    }

    /**
     * @param CancelWorkoutDataInput $input
     */
    public function execute(DataInputInterface $input): WorkoutDataOutput
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $workout = $this->workoutProvider->findOneByIdForPlayerAction($input->id, $player);
        if (null === $workout) {
            throw new EntityNotFoundException(sprintf('Workout "%s" not found.', $input->id));
        }

        $this->cancelWorkoutValidator->validate($player, $input, $workout);

        $workout->status = WorkoutStatusRegistry::CANCELED;

        $this->workoutPersister->update($workout);

        return $this->mapper->map($workout, WorkoutDataOutput::class);
    }
}
