<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Player\Tracking;

use App\Domain\DTO\DataInput\Player\Tracking\Sleep\DeleteSleepDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Sleep\ListSleepForRangeDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Sleep\LogSleepDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Sleep\UpdateSleepDataInput;
use App\Domain\Exception\ValidationException;
use App\UseCase\Player\Tracking\Sleep\DeleteSleepUseCase;
use App\UseCase\Player\Tracking\Sleep\ListSleepForRangeUseCase;
use App\UseCase\Player\Tracking\Sleep\LogSleepUseCase;
use App\UseCase\Player\Tracking\Sleep\UpdateSleepUseCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class SleepPlayerController
{
    private const string BODY_INVALID = 'SLEEP_BODY_INVALID';

    #[Route(path: '/api/player/tracking/sleep', name: 'player_tracking_sleep_log', methods: ['POST'])]
    public function log(Request $request, LogSleepUseCase $useCase): JsonResponse
    {
        /** @var array{bedAt?: string, wakeAt?: string, quality?: int} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        $output = $useCase->execute(new LogSleepDataInput(
            self::parseDate((string) ($payload['bedAt'] ?? ''), 'bedAt'),
            self::parseDate((string) ($payload['wakeAt'] ?? ''), 'wakeAt'),
            isset($payload['quality']) ? (int) $payload['quality'] : null,
        ));

        return new JsonResponse($output, 201);
    }

    #[Route(path: '/api/player/tracking/sleep/{id}', name: 'player_tracking_sleep_update', methods: ['PUT'])]
    public function update(string $id, Request $request, UpdateSleepUseCase $useCase): JsonResponse
    {
        /** @var array{bedAt?: string, wakeAt?: string, quality?: int} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        return new JsonResponse($useCase->execute(new UpdateSleepDataInput(
            $id,
            self::parseDate((string) ($payload['bedAt'] ?? ''), 'bedAt'),
            self::parseDate((string) ($payload['wakeAt'] ?? ''), 'wakeAt'),
            isset($payload['quality']) ? (int) $payload['quality'] : null,
        )));
    }

    #[Route(path: '/api/player/tracking/sleep/{id}', name: 'player_tracking_sleep_delete', methods: ['DELETE'])]
    public function delete(string $id, DeleteSleepUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new DeleteSleepDataInput($id)));
    }

    #[Route(path: '/api/player/tracking/sleep', name: 'player_tracking_sleep_list', methods: ['GET'])]
    public function list(Request $request, ListSleepForRangeUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new ListSleepForRangeDataInput(
            self::parseDate((string) $request->query->get('from', ''), 'from'),
            self::parseDate((string) $request->query->get('to', ''), 'to'),
        )));
    }

    private static function parseDate(string $raw, string $field): \DateTimeImmutable
    {
        if ('' === $raw) {
            throw new ValidationException('Sleep request is invalid.', [$field => [sprintf('%s is required.', $field)]], self::BODY_INVALID);
        }

        try {
            return new \DateTimeImmutable($raw);
        } catch (\Exception) {
            throw new ValidationException('Sleep request is invalid.', [$field => [sprintf('%s is not a valid datetime.', $field)]], self::BODY_INVALID);
        }
    }
}
