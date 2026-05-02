<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Player\Training;

use App\Domain\DTO\DataInput\Player\Training\ExerciseSet\AddExerciseSetDataInput;
use App\Domain\DTO\DataInput\Player\Training\ExerciseSet\RemoveExerciseSetDataInput;
use App\Domain\DTO\DataInput\Player\Training\ExerciseSet\UpdateExerciseSetAchievedDataInput;
use App\Domain\DTO\DataInput\Player\Training\ExerciseSet\UpdateExerciseSetPlannedDataInput;
use App\Domain\Exception\ValidationException;
use App\UseCase\Player\Training\ExerciseSet\AddExerciseSetUseCase;
use App\UseCase\Player\Training\ExerciseSet\RemoveExerciseSetUseCase;
use App\UseCase\Player\Training\ExerciseSet\UpdateExerciseSetAchievedUseCase;
use App\UseCase\Player\Training\ExerciseSet\UpdateExerciseSetPlannedUseCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ExerciseSetPlayerController
{
    private const string SET_BODY_INVALID = 'EXERCISE_SET_BODY_INVALID';

    #[Route(path: '/api/player/exercises/{exerciseId}/sets', name: 'player_exercise_set_add', methods: ['POST'])]
    public function add(string $exerciseId, Request $request, AddExerciseSetUseCase $useCase): JsonResponse
    {
        $p = self::decodePlannedPayload($request);
        $a = self::decodeAchievedPayload($request);

        // Both planned* and achieved* are forwarded; the validator decides which group is allowed
        // based on the workout status (PLANNED → planned*, IN_PROGRESS → achieved*).
        $output = $useCase->execute(new AddExerciseSetDataInput(
            $exerciseId,
            $p['plannedReps'],
            $p['plannedWeight'],
            $p['plannedDurationSeconds'],
            $p['plannedDistanceMeters'],
            $p['plannedInclinePercent'],
            $p['plannedInclineMeters'],
            $a['achievedReps'],
            $a['achievedWeight'],
            $a['achievedDurationSeconds'],
            $a['achievedDistanceMeters'],
            $a['achievedInclinePercent'],
            $a['achievedInclineMeters'],
        ));

        return new JsonResponse($output, 201);
    }

    #[Route(path: '/api/player/sets/{id}/planned', name: 'player_exercise_set_update_planned', methods: ['PUT'])]
    public function updatePlanned(string $id, Request $request, UpdateExerciseSetPlannedUseCase $useCase): JsonResponse
    {
        $p = self::decodePlannedPayload($request);

        return new JsonResponse($useCase->execute(new UpdateExerciseSetPlannedDataInput(
            $id,
            $p['plannedReps'],
            $p['plannedWeight'],
            $p['plannedDurationSeconds'],
            $p['plannedDistanceMeters'],
            $p['plannedInclinePercent'],
            $p['plannedInclineMeters'],
        )));
    }

    #[Route(path: '/api/player/sets/{id}/achieved', name: 'player_exercise_set_update_achieved', methods: ['PUT'])]
    public function updateAchieved(string $id, Request $request, UpdateExerciseSetAchievedUseCase $useCase): JsonResponse
    {
        $p = self::decodeAchievedPayload($request);

        return new JsonResponse($useCase->execute(new UpdateExerciseSetAchievedDataInput(
            $id,
            $p['achievedReps'],
            $p['achievedWeight'],
            $p['achievedDurationSeconds'],
            $p['achievedDistanceMeters'],
            $p['achievedInclinePercent'],
            $p['achievedInclineMeters'],
        )));
    }

    #[Route(path: '/api/player/sets/{id}', name: 'player_exercise_set_remove', methods: ['DELETE'])]
    public function remove(string $id, RemoveExerciseSetUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new RemoveExerciseSetDataInput($id)));
    }

    /**
     * @return array{plannedReps: int|null, plannedWeight: numeric-string|null, plannedDurationSeconds: int|null, plannedDistanceMeters: numeric-string|null, plannedInclinePercent: numeric-string|null, plannedInclineMeters: numeric-string|null}
     */
    private static function decodePlannedPayload(Request $request): array
    {
        /** @var array{plannedReps?: int|null, plannedWeight?: string|null, plannedDurationSeconds?: int|null, plannedDistanceMeters?: string|null, plannedInclinePercent?: string|null, plannedInclineMeters?: string|null} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        return [
            'plannedReps' => $payload['plannedReps'] ?? null,
            'plannedWeight' => self::asNumericStringOrNull($payload['plannedWeight'] ?? null, 'plannedWeight'),
            'plannedDurationSeconds' => $payload['plannedDurationSeconds'] ?? null,
            'plannedDistanceMeters' => self::asNumericStringOrNull($payload['plannedDistanceMeters'] ?? null, 'plannedDistanceMeters'),
            'plannedInclinePercent' => self::asNumericStringOrNull($payload['plannedInclinePercent'] ?? null, 'plannedInclinePercent'),
            'plannedInclineMeters' => self::asNumericStringOrNull($payload['plannedInclineMeters'] ?? null, 'plannedInclineMeters'),
        ];
    }

    /**
     * @return array{achievedReps: int|null, achievedWeight: numeric-string|null, achievedDurationSeconds: int|null, achievedDistanceMeters: numeric-string|null, achievedInclinePercent: numeric-string|null, achievedInclineMeters: numeric-string|null}
     */
    private static function decodeAchievedPayload(Request $request): array
    {
        /** @var array{achievedReps?: int|null, achievedWeight?: string|null, achievedDurationSeconds?: int|null, achievedDistanceMeters?: string|null, achievedInclinePercent?: string|null, achievedInclineMeters?: string|null} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        return [
            'achievedReps' => $payload['achievedReps'] ?? null,
            'achievedWeight' => self::asNumericStringOrNull($payload['achievedWeight'] ?? null, 'achievedWeight'),
            'achievedDurationSeconds' => $payload['achievedDurationSeconds'] ?? null,
            'achievedDistanceMeters' => self::asNumericStringOrNull($payload['achievedDistanceMeters'] ?? null, 'achievedDistanceMeters'),
            'achievedInclinePercent' => self::asNumericStringOrNull($payload['achievedInclinePercent'] ?? null, 'achievedInclinePercent'),
            'achievedInclineMeters' => self::asNumericStringOrNull($payload['achievedInclineMeters'] ?? null, 'achievedInclineMeters'),
        ];
    }

    /**
     * @return numeric-string|null
     */
    private static function asNumericStringOrNull(?string $value, string $fieldName): ?string
    {
        if (null === $value) {
            return null;
        }
        if (false === is_numeric($value) || 1 !== preg_match('/^\d+(\.\d+)?$/', $value)) {
            throw new ValidationException('Exercise set body is invalid.', [$fieldName => [sprintf('%s must be a non-negative numeric value.', $fieldName)]], self::SET_BODY_INVALID);
        }

        return $value;
    }
}
