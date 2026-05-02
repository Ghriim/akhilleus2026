<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\Workout;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\Workout\StartPlannedWorkoutDataInput;
use App\Domain\DTO\DataOutput\Player\Training\Workout\WorkoutDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Gateway\Persister\Training\Workout\WorkoutPersisterGateway;
use App\Domain\Gateway\Provider\Training\Workout\WorkoutProviderGateway;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Workout\StartPlannedWorkoutValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Psr\Clock\ClockInterface;

final class StartPlannedWorkoutUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly StartPlannedWorkoutValidator $startPlannedWorkoutValidator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly WorkoutProviderGateway $workoutProvider,
        private readonly WorkoutPersisterGateway $workoutPersister,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @param StartPlannedWorkoutDataInput $input
     */
    public function execute(DataInputInterface $input): WorkoutDataOutput
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $workout = $this->workoutProvider->findOneByIdForPlayerAction($input->id, $player);
        if (null === $workout) {
            throw new EntityNotFoundException(sprintf('Workout "%s" not found.', $input->id));
        }

        $this->startPlannedWorkoutValidator->validate($player, $input, $workout);

        $workout->status = WorkoutStatusRegistry::IN_PROGRESS;
        $workout->dateStart = $this->clock->now();

        $this->workoutPersister->update($workout);

        return new WorkoutDataOutput(
            $workout->id,
            $workout->name,
            $workout->status,
            $workout->plannedAt?->format(\DateTimeInterface::ATOM),
            $workout->dateStart->format(\DateTimeInterface::ATOM),
            $workout->dateEnd?->format(\DateTimeInterface::ATOM),
        );
    }
}
