<?php

declare(strict_types=1);

namespace App\Domain\Validator\Player\Training\Workout;

use App\Domain\DTO\DataInput\Player\Training\Workout\PlanWorkoutDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Security\LoggedPlayerResolverInterface;
use App\Domain\Validator\AbstractLoggedPlayerValidator;
use Psr\Clock\ClockInterface;

final readonly class PlanWorkoutValidator extends AbstractLoggedPlayerValidator
{
    public const string ERROR_CODE = 'PLAN_WORKOUT_VALIDATION_FAILED';

    public function __construct(
        LoggedPlayerResolverInterface $loggedPlayerResolver,
        private ClockInterface $clock,
    ) {
        parent::__construct($loggedPlayerResolver);
    }

    public function validate(object $input): void
    {
        if (false === $input instanceof PlanWorkoutDataInput) {
            throw new \LogicException(sprintf('Expected %s, got %s.', PlanWorkoutDataInput::class, $input::class));
        }

        $violations = [];
        if ($input->plannedAt <= $this->clock->now()) {
            $violations['plannedAt'][] = 'Planned date must be in the future.';
        }

        if ([] !== $violations) {
            throw new ValidationException('Workout planning data is invalid.', $violations, self::ERROR_CODE);
        }
    }
}
