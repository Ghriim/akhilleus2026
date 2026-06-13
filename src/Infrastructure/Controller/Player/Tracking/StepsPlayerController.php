<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Player\Tracking;

use App\Domain\DTO\DataInput\Player\Tracking\Steps\DeleteStepsForDayDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Steps\GetTodayStepsDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Steps\ListStepsForRangeDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Steps\UpdatePlayerDailyStepsTargetDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Steps\UpdateStepsDailyTargetDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Steps\UpsertStepsForDayDataInput;
use App\Domain\Exception\ValidationException;
use App\UseCase\Player\Tracking\Steps\DeleteStepsForDayUseCase;
use App\UseCase\Player\Tracking\Steps\GetTodayStepsUseCase;
use App\UseCase\Player\Tracking\Steps\ListStepsForRangeUseCase;
use App\UseCase\Player\Tracking\Steps\UpdatePlayerDailyStepsTargetUseCase;
use App\UseCase\Player\Tracking\Steps\UpdateStepsDailyTargetUseCase;
use App\UseCase\Player\Tracking\Steps\UpsertStepsForDayUseCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class StepsPlayerController
{
    private const string BODY_INVALID = 'STEPS_BODY_INVALID';

    // `{date}` is constrained to an ISO date so the static `/steps/today` and `/steps/target`
    // routes below are never swallowed by the `/steps/{date}` pattern.
    private const string DATE_REQUIREMENT = '\d{4}-\d{2}-\d{2}';

    #[Route(path: '/api/player/tracking/steps/today', name: 'player_tracking_steps_today', methods: ['GET'])]
    public function today(GetTodayStepsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new GetTodayStepsDataInput()));
    }

    #[Route(path: '/api/player/tracking/steps/today/target', name: 'player_tracking_steps_today_target', methods: ['PUT'])]
    public function updateTodayTarget(Request $request, UpdateStepsDailyTargetUseCase $useCase): JsonResponse
    {
        /** @var array{target?: int} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        return new JsonResponse($useCase->execute(new UpdateStepsDailyTargetDataInput((int) ($payload['target'] ?? 0))));
    }

    #[Route(path: '/api/player/tracking/steps/target', name: 'player_tracking_steps_player_target', methods: ['PUT'])]
    public function updatePlayerTarget(Request $request, UpdatePlayerDailyStepsTargetUseCase $useCase): JsonResponse
    {
        /** @var array{target?: int} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        return new JsonResponse($useCase->execute(new UpdatePlayerDailyStepsTargetDataInput((int) ($payload['target'] ?? 0))));
    }

    #[Route(path: '/api/player/tracking/steps/{date}', name: 'player_tracking_steps_upsert', methods: ['PUT'], requirements: ['date' => self::DATE_REQUIREMENT])]
    public function upsert(string $date, Request $request, UpsertStepsForDayUseCase $useCase): JsonResponse
    {
        /** @var array{count?: int} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        return new JsonResponse($useCase->execute(new UpsertStepsForDayDataInput(
            self::parseDate($date, 'date'),
            (int) ($payload['count'] ?? 0),
        )));
    }

    #[Route(path: '/api/player/tracking/steps/{date}', name: 'player_tracking_steps_delete', methods: ['DELETE'], requirements: ['date' => self::DATE_REQUIREMENT])]
    public function delete(string $date, DeleteStepsForDayUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new DeleteStepsForDayDataInput(self::parseDate($date, 'date'))));
    }

    #[Route(path: '/api/player/tracking/steps', name: 'player_tracking_steps_list', methods: ['GET'])]
    public function list(Request $request, ListStepsForRangeUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new ListStepsForRangeDataInput(
            self::parseDate($request->query->get('from'), 'from'),
            self::parseDate($request->query->get('to'), 'to'),
        )));
    }

    private static function parseDate(?string $raw, string $field): \DateTimeImmutable
    {
        if (null === $raw || '' === $raw) {
            throw new ValidationException('Steps request is invalid.', [$field => [sprintf('%s is required.', $field)]], self::BODY_INVALID);
        }

        try {
            return new \DateTimeImmutable($raw);
        } catch (\Exception) {
            throw new ValidationException('Steps request is invalid.', [$field => [sprintf('%s is not a valid date.', $field)]], self::BODY_INVALID);
        }
    }
}
