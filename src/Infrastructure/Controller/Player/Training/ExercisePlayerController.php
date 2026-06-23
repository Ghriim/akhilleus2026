<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Player\Training;

use App\Domain\DTO\DataInput\Player\Training\Exercise\AddMovementToWorkoutDataInput;
use App\Domain\DTO\DataInput\Player\Training\Exercise\RemoveMovementFromWorkoutDataInput;
use App\Domain\DTO\DataInput\Player\Training\Exercise\ReorderMovementsDataInput;
use App\Domain\DTO\DataInput\Player\Training\Exercise\UpdateMovementRestDurationDataInput;
use App\UseCase\Player\Training\Exercise\AddMovementToWorkoutUseCase;
use App\UseCase\Player\Training\Exercise\RemoveMovementFromWorkoutUseCase;
use App\UseCase\Player\Training\Exercise\ReorderMovementsUseCase;
use App\UseCase\Player\Training\Exercise\UpdateMovementRestDurationUseCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ExercisePlayerController
{
    #[Route(path: '/api/player/workouts/{workoutId}/exercises', name: 'player_exercise_add', methods: ['POST'])]
    public function add(string $workoutId, Request $request, AddMovementToWorkoutUseCase $useCase): JsonResponse
    {
        /** @var array{movementId?: string, restDurationSeconds?: int} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        $output = $useCase->execute(new AddMovementToWorkoutDataInput(
            $workoutId,
            (string) ($payload['movementId'] ?? ''),
            (int) ($payload['restDurationSeconds'] ?? 0),
        ));

        return new JsonResponse($output, 201);
    }

    #[Route(path: '/api/player/exercises/{id}', name: 'player_exercise_remove', methods: ['DELETE'])]
    public function remove(string $id, RemoveMovementFromWorkoutUseCase $useCase): JsonResponse
    {
        $useCase->execute(new RemoveMovementFromWorkoutDataInput($id));

        return new JsonResponse(null, 204);
    }

    #[Route(path: '/api/player/exercises/{id}/rest-duration', name: 'player_exercise_update_rest', methods: ['PUT'])]
    public function updateRestDuration(string $id, Request $request, UpdateMovementRestDurationUseCase $useCase): JsonResponse
    {
        /** @var array{restDurationSeconds?: int} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        return new JsonResponse($useCase->execute(new UpdateMovementRestDurationDataInput(
            $id,
            (int) ($payload['restDurationSeconds'] ?? 0),
        )));
    }

    #[Route(path: '/api/player/workouts/{workoutId}/exercises/reorder', name: 'player_exercise_reorder', methods: ['POST'])]
    public function reorder(string $workoutId, Request $request, ReorderMovementsUseCase $useCase): JsonResponse
    {
        /** @var array{orderedExerciseIds?: list<string>} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];
        $orderedExerciseIds = $payload['orderedExerciseIds'] ?? [];

        return new JsonResponse($useCase->execute(new ReorderMovementsDataInput($workoutId, $orderedExerciseIds)));
    }
}
