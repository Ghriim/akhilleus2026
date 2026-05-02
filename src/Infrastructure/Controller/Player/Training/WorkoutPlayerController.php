<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Player\Training;

use App\Domain\DTO\DataInput\Player\Training\Workout\CancelWorkoutDataInput;
use App\Domain\DTO\DataInput\Player\Training\Workout\FinishWorkoutDataInput;
use App\Domain\DTO\DataInput\Player\Training\Workout\GetWorkoutDetailsDataInput;
use App\Domain\DTO\DataInput\Player\Training\Workout\ListUpcomingWorkoutsDataInput;
use App\Domain\DTO\DataInput\Player\Training\Workout\ListWorkoutHistoryDataInput;
use App\Domain\DTO\DataInput\Player\Training\Workout\ListWorkoutsByMonthDataInput;
use App\Domain\DTO\DataInput\Player\Training\Workout\PlanWorkoutDataInput;
use App\Domain\DTO\DataInput\Player\Training\Workout\StartEmptyWorkoutDataInput;
use App\Domain\DTO\DataInput\Player\Training\Workout\StartPlannedWorkoutDataInput;
use App\Domain\Exception\ValidationException;
use App\UseCase\Player\Training\Workout\CancelWorkoutUseCase;
use App\UseCase\Player\Training\Workout\FinishWorkoutUseCase;
use App\UseCase\Player\Training\Workout\GetWorkoutDetailsUseCase;
use App\UseCase\Player\Training\Workout\ListUpcomingWorkoutsUseCase;
use App\UseCase\Player\Training\Workout\ListWorkoutHistoryUseCase;
use App\UseCase\Player\Training\Workout\ListWorkoutsByMonthUseCase;
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

    #[Route(path: '/api/player/workouts/history', name: 'player_workout_list_history', methods: ['GET'])]
    public function history(Request $request, ListWorkoutHistoryUseCase $useCase): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', '1'));
        $perPage = (int) $request->query->get('perPage', (string) ListWorkoutHistoryDataInput::DEFAULT_PER_PAGE);

        return new JsonResponse($useCase->execute(new ListWorkoutHistoryDataInput($page, $perPage)));
    }

    #[Route(path: '/api/player/workouts/upcoming', name: 'player_workout_list_upcoming', methods: ['GET'])]
    public function upcoming(ListUpcomingWorkoutsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new ListUpcomingWorkoutsDataInput()));
    }

    #[Route(path: '/api/player/workouts/calendar', name: 'player_workout_list_by_month', methods: ['GET'])]
    public function calendar(Request $request, ListWorkoutsByMonthUseCase $useCase): JsonResponse
    {
        $year = (int) $request->query->get('year', '0');
        $month = (int) $request->query->get('month', '0');

        return new JsonResponse($useCase->execute(new ListWorkoutsByMonthDataInput($year, $month)));
    }

    #[Route(path: '/api/player/workouts/{id}', name: 'player_workout_details', methods: ['GET'], requirements: ['id' => '[0-9A-HJKMNP-TV-Z]{26}'])]
    public function details(string $id, GetWorkoutDetailsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new GetWorkoutDetailsDataInput($id)));
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

    #[Route(path: '/api/player/workouts/{id}/finish', name: 'player_workout_finish', methods: ['POST'])]
    public function finish(string $id, FinishWorkoutUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new FinishWorkoutDataInput($id)));
    }
}
