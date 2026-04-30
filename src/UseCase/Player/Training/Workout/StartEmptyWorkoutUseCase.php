<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\Workout;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\Workout\StartEmptyWorkoutDataInput;
use App\Domain\DTO\DataModel\Training\Workout\WorkoutDataModel;
use App\Domain\DTO\DataOutput\Player\Training\Workout\WorkoutDataOutput;
use App\Domain\Gateway\Persister\Training\Workout\WorkoutPersisterGateway;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Workout\StartEmptyWorkoutValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;
use Psr\Clock\ClockInterface;

final class StartEmptyWorkoutUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly StartEmptyWorkoutValidator $startEmptyWorkoutValidator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly WorkoutPersisterGateway $workoutPersister,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct($startEmptyWorkoutValidator);
    }

    public function execute(StartEmptyWorkoutDataInput|DataInputInterface $input): WorkoutDataOutput
    {
        if (false === $input instanceof StartEmptyWorkoutDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', StartEmptyWorkoutDataInput::class, $input::class));
        }

        $this->startEmptyWorkoutValidator->validate($input);

        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $workout = new WorkoutDataModel($player, WorkoutStatusRegistry::IN_PROGRESS);
        $workout->dateStart = $this->clock->now();

        $this->workoutPersister->create($workout);

        return new WorkoutDataOutput(
            $workout->id,
            $workout->status,
            $workout->plannedAt?->format(\DateTimeInterface::ATOM),
            $workout->dateStart->format(\DateTimeInterface::ATOM),
            $workout->dateEnd?->format(\DateTimeInterface::ATOM),
        );
    }
}
