<?php

declare(strict_types=1);

namespace App\UseCase\Player\Training\Workout;

use App\Domain\DTO\DataInput\DataInputInterface;
use App\Domain\DTO\DataInput\Player\Training\Workout\CancelWorkoutDataInput;
use App\Domain\DTO\DataOutput\Player\Training\Workout\WorkoutDataOutput;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Training\Workout\WorkoutPersisterGateway;
use App\Domain\Gateway\Provider\Training\Workout\WorkoutProviderGateway;
use App\Domain\Registry\Training\Workout\WorkoutStatusRegistry;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\Player\Training\Workout\CancelWorkoutValidator;
use App\UseCase\AbstractLoggedPlayerUseCase;

final class CancelWorkoutUseCase extends AbstractLoggedPlayerUseCase
{
    public const string ERROR_CODE = 'CANCEL_WORKOUT_ILLEGAL_STATE';

    /** @var list<string> */
    private const array CANCELLABLE_STATUSES = [
        WorkoutStatusRegistry::PLANNED,
        WorkoutStatusRegistry::IN_PROGRESS,
    ];

    public function __construct(
        private readonly CancelWorkoutValidator $cancelWorkoutValidator,
        private readonly LoggedPlayerResolverInterface $loggedPlayerResolver,
        private readonly WorkoutProviderGateway $workoutProvider,
        private readonly WorkoutPersisterGateway $workoutPersister,
    ) {
        parent::__construct($cancelWorkoutValidator);
    }

    public function execute(CancelWorkoutDataInput|DataInputInterface $input): WorkoutDataOutput
    {
        if (false === $input instanceof CancelWorkoutDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', CancelWorkoutDataInput::class, $input::class));
        }

        $this->cancelWorkoutValidator->validate($input);

        $player = $this->loggedPlayerResolver->getLoggedPlayer();
        $workout = $this->workoutProvider->findOneByIdForPlayerAction($input->id, $player);
        if (null === $workout) {
            throw new EntityNotFoundException(sprintf('Workout "%s" not found.', $input->id));
        }

        if (false === in_array($workout->status, self::CANCELLABLE_STATUSES, true)) {
            throw new ValidationException('Only a planned or in-progress workout can be canceled.', ['status' => [sprintf('Workout is in status "%s", expected one of: %s.', $workout->status, implode(', ', self::CANCELLABLE_STATUSES))]], self::ERROR_CODE);
        }

        $workout->status = WorkoutStatusRegistry::CANCELED;

        $this->workoutPersister->update($workout);

        return new WorkoutDataOutput(
            $workout->id,
            $workout->status,
            $workout->plannedAt?->format(\DateTimeInterface::ATOM),
            $workout->dateStart?->format(\DateTimeInterface::ATOM),
            $workout->dateEnd?->format(\DateTimeInterface::ATOM),
        );
    }
}
