<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\StartEmptyWorkoutDataInput;
use App\Domain\DTO\DataModel\User\PlayerDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\Workout\WorkoutProviderGateway;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\AbstractLoggedPlayerValidator;

final readonly class StartEmptyWorkoutValidator extends AbstractLoggedPlayerValidator
{
    public const string ALREADY_IN_PROGRESS_CODE = 'WORKOUT_ALREADY_IN_PROGRESS';

    public function __construct(
        LoggedPlayerResolverInterface $loggedPlayerResolver,
        private WorkoutProviderGateway $workoutProvider,
    ) {
        parent::__construct($loggedPlayerResolver);
    }

    public function validate(PlayerDataModel $player, StartEmptyWorkoutDataInput $input): void
    {
        unset($input);

        $existing = $this->workoutProvider->findInProgressByPlayer($player);
        if (null !== $existing) {
            throw new ValidationException('A workout is already in progress; resume it before starting a new one.', ['status' => [sprintf('Workout "%s" is already in progress.', $existing->id)]], self::ALREADY_IN_PROGRESS_CODE);
        }
    }
}
