<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\Workout;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\Workout\ListWorkoutHistoryDataInput;
use App\Domain\DTO\DataOutput\Player\Training\Workout\WorkoutDataOutput;
use App\Domain\DTO\DataOutput\Player\Training\Workout\WorkoutHistoryDataOutput;
use App\Domain\Gateway\Provider\Training\Workout\WorkoutProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Workout\ListWorkoutHistoryValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;

final class ListWorkoutHistoryUseCase extends AbstractLoggedPlayerUseCase
{
    public function __construct(
        private readonly ListWorkoutHistoryValidator $listWorkoutHistoryValidator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly WorkoutProviderGateway $workoutProvider,
    ) {
    }

    /**
     * @param ListWorkoutHistoryDataInput $input
     */
    public function execute(DataInputInterface $input): WorkoutHistoryDataOutput
    {
        $this->listWorkoutHistoryValidator->validate($input);

        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $workouts = $this->workoutProvider->findCompletedByPlayer($player, $input->page, $input->perPage);
        $totalCount = $this->workoutProvider->countCompletedByPlayer($player);

        $items = array_map(
            static fn ($workout) => new WorkoutDataOutput(
                $workout->id,
                $workout->name,
                $workout->status,
                $workout->plannedAt?->format(\DateTimeInterface::ATOM),
                $workout->dateStart?->format(\DateTimeInterface::ATOM),
                $workout->dateEnd?->format(\DateTimeInterface::ATOM),
                $workout->duration,
                $workout->volume,
                $workout->distance,
                $workout->inclineMeters,
            ),
            $workouts,
        );

        return new WorkoutHistoryDataOutput($items, $input->page, $input->perPage, $totalCount);
    }
}
