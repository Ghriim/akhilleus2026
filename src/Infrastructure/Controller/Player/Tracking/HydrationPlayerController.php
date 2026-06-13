<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Player\Tracking;

use App\Domain\DTO\DataInput\Player\Tracking\Hydration\AddHydrationEntryDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Hydration\DeleteHydrationEntryDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Hydration\GetTodayHydrationDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Hydration\UpdateHydrationDailyTargetDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Hydration\UpdateHydrationEntryDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Hydration\UpdatePlayerDailyHydrationTargetDataInput;
use App\Domain\Exception\ValidationException;
use App\UseCase\Player\Tracking\Hydration\AddHydrationEntryUseCase;
use App\UseCase\Player\Tracking\Hydration\DeleteHydrationEntryUseCase;
use App\UseCase\Player\Tracking\Hydration\GetTodayHydrationUseCase;
use App\UseCase\Player\Tracking\Hydration\UpdateHydrationDailyTargetUseCase;
use App\UseCase\Player\Tracking\Hydration\UpdateHydrationEntryUseCase;
use App\UseCase\Player\Tracking\Hydration\UpdatePlayerDailyHydrationTargetUseCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class HydrationPlayerController
{
    private const string BODY_INVALID = 'HYDRATION_BODY_INVALID';

    #[Route(path: '/api/player/tracking/hydration/today', name: 'player_tracking_hydration_today', methods: ['GET'])]
    public function today(GetTodayHydrationUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new GetTodayHydrationDataInput()));
    }

    #[Route(path: '/api/player/tracking/hydration/today/target', name: 'player_tracking_hydration_today_target', methods: ['PUT'])]
    public function updateTodayTarget(Request $request, UpdateHydrationDailyTargetUseCase $useCase): JsonResponse
    {
        /** @var array{targetMl?: int} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        return new JsonResponse($useCase->execute(new UpdateHydrationDailyTargetDataInput((int) ($payload['targetMl'] ?? 0))));
    }

    #[Route(path: '/api/player/tracking/hydration/target', name: 'player_tracking_hydration_player_target', methods: ['PUT'])]
    public function updatePlayerTarget(Request $request, UpdatePlayerDailyHydrationTargetUseCase $useCase): JsonResponse
    {
        /** @var array{targetMl?: int} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        return new JsonResponse($useCase->execute(new UpdatePlayerDailyHydrationTargetDataInput((int) ($payload['targetMl'] ?? 0))));
    }

    #[Route(path: '/api/player/tracking/hydration/entries', name: 'player_tracking_hydration_entry_add', methods: ['POST'])]
    public function addEntry(Request $request, AddHydrationEntryUseCase $useCase): JsonResponse
    {
        /** @var array{loggedAt?: string, valueMl?: int} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        $output = $useCase->execute(new AddHydrationEntryDataInput(
            self::parseDate((string) ($payload['loggedAt'] ?? ''), 'loggedAt'),
            (int) ($payload['valueMl'] ?? 0),
        ));

        return new JsonResponse($output, 201);
    }

    #[Route(path: '/api/player/tracking/hydration/entries/{id}', name: 'player_tracking_hydration_entry_update', methods: ['PUT'])]
    public function updateEntry(string $id, Request $request, UpdateHydrationEntryUseCase $useCase): JsonResponse
    {
        /** @var array{valueMl?: int} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        return new JsonResponse($useCase->execute(new UpdateHydrationEntryDataInput($id, (int) ($payload['valueMl'] ?? 0))));
    }

    #[Route(path: '/api/player/tracking/hydration/entries/{id}', name: 'player_tracking_hydration_entry_delete', methods: ['DELETE'])]
    public function deleteEntry(string $id, DeleteHydrationEntryUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new DeleteHydrationEntryDataInput($id)));
    }

    private static function parseDate(string $raw, string $field): \DateTimeImmutable
    {
        if ('' === $raw) {
            throw new ValidationException('Hydration request is invalid.', [$field => [sprintf('%s is required.', $field)]], self::BODY_INVALID);
        }

        try {
            return new \DateTimeImmutable($raw);
        } catch (\Exception) {
            throw new ValidationException('Hydration request is invalid.', [$field => [sprintf('%s is not a valid datetime.', $field)]], self::BODY_INVALID);
        }
    }
}
