<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Player\Training;

use App\Domain\DTO\DataInput\Player\Training\Workout\CancelWorkoutDataInput;
use App\Domain\DTO\DataInput\Player\Training\Workout\PlanWorkoutDataInput;
use App\Domain\DTO\DataInput\Player\Training\Workout\StartEmptyWorkoutDataInput;
use App\Domain\DTO\DataInput\Player\Training\Workout\StartPlannedWorkoutDataInput;
use App\Domain\Exception\ValidationException;
use App\UseCase\Player\Training\Workout\CancelWorkoutUseCase;
use App\UseCase\Player\Training\Workout\PlanWorkoutUseCase;
use App\UseCase\Player\Training\Workout\StartEmptyWorkoutUseCase;
use App\UseCase\Player\Training\Workout\StartPlannedWorkoutUseCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class WorkoutPlayerController
{
    private const string PLAN_WORKOUT_BODY_INVALID = 'PLAN_WORKOUT_BODY_INVALID';

    #[Route(path: '/api/player/workouts', name: 'player_workout_start_empty', methods: ['POST'])]
    public function startEmpty(StartEmptyWorkoutUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new StartEmptyWorkoutDataInput()), 201);
    }

    #[Route(path: '/api/player/workouts/planned', name: 'player_workout_plan', methods: ['POST'])]
    public function plan(Request $request, PlanWorkoutUseCase $useCase): JsonResponse
    {
        /** @var array{plannedAt?: string} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];
        $rawPlannedAt = (string) ($payload['plannedAt'] ?? '');

        if ('' === $rawPlannedAt) {
            throw new ValidationException('Workout planning data is invalid.', ['plannedAt' => ['Planned date is required.']], self::PLAN_WORKOUT_BODY_INVALID);
        }

        try {
            $plannedAt = new \DateTimeImmutable($rawPlannedAt);
        } catch (\Exception) {
            throw new ValidationException('Workout planning data is invalid.', ['plannedAt' => ['Planned date is not a valid ISO 8601 datetime.']], self::PLAN_WORKOUT_BODY_INVALID);
        }

        return new JsonResponse($useCase->execute(new PlanWorkoutDataInput($plannedAt)), 201);
    }

    #[Route(path: '/api/player/workouts/{id}/start', name: 'player_workout_start_planned', methods: ['POST'])]
    public function startPlanned(string $id, StartPlannedWorkoutUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new StartPlannedWorkoutDataInput($id)));
    }

    #[Route(path: '/api/player/workouts/{id}/cancel', name: 'player_workout_cancel', methods: ['POST'])]
    public function cancel(string $id, CancelWorkoutUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new CancelWorkoutDataInput($id)));
    }
}
