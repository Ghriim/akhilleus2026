<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\Workout;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\Workout\ListUpcomingWorkoutsDataInput;
use App\Domain\DTO\DataOutput\Player\Training\Workout\WorkoutDataOutput;
use App\Domain\Gateway\Provider\Training\Workout\WorkoutProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\UseCase\AbstractLoggedPlayerUseCase;

final class ListUpcomingWorkoutsUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly WorkoutProviderGateway $workoutProvider,
    ) {
    }

    /**
     * @param ListUpcomingWorkoutsDataInput $input
     *
     * @return list<WorkoutDataOutput>
     */
    public function execute(DataInputInterface $input): array
    {
        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $workouts = $this->workoutProvider->findPlannedOrInProgressByPlayer($player);

        return array_map(
            static fn ($workout) => new WorkoutDataOutput(
                $workout->id,
                $workout->status,
                $workout->plannedAt?->format(\DateTimeInterface::ATOM),
                $workout->dateStart?->format(\DateTimeInterface::ATOM),
                $workout->dateEnd?->format(\DateTimeInterface::ATOM),
            ),
            $workouts,
        );
    }
}
