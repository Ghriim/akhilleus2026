<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Player\Tracking;

use App\Domain\DTO\DataInput\Player\Tracking\Weight\DeleteWeightDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Weight\ListWeightForRangeDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Weight\LogWeightDataInput;
use App\Domain\DTO\DataInput\Player\Tracking\Weight\UpdateWeightDataInput;
use App\Domain\Exception\ValidationException;
use App\UseCase\Player\Tracking\Weight\DeleteWeightUseCase;
use App\UseCase\Player\Tracking\Weight\ListWeightForRangeUseCase;
use App\UseCase\Player\Tracking\Weight\LogWeightUseCase;
use App\UseCase\Player\Tracking\Weight\UpdateWeightUseCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class WeightPlayerController
{
    private const string BODY_INVALID = 'WEIGHT_BODY_INVALID';

    #[Route(path: '/api/player/tracking/weight', name: 'player_tracking_weight_log', methods: ['POST'])]
    public function log(Request $request, LogWeightUseCase $useCase): JsonResponse
    {
        /** @var array{loggedAt?: string, valueGrams?: int} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        $output = $useCase->execute(new LogWeightDataInput(
            self::parseDate((string) ($payload['loggedAt'] ?? ''), 'loggedAt'),
            (int) ($payload['valueGrams'] ?? 0),
        ));

        return new JsonResponse($output, 201);
    }

    #[Route(path: '/api/player/tracking/weight/{id}', name: 'player_tracking_weight_update', methods: ['PUT'])]
    public function update(string $id, Request $request, UpdateWeightUseCase $useCase): JsonResponse
    {
        /** @var array{loggedAt?: string, valueGrams?: int} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        return new JsonResponse($useCase->execute(new UpdateWeightDataInput(
            $id,
            self::parseDate((string) ($payload['loggedAt'] ?? ''), 'loggedAt'),
            (int) ($payload['valueGrams'] ?? 0),
        )));
    }

    #[Route(path: '/api/player/tracking/weight/{id}', name: 'player_tracking_weight_delete', methods: ['DELETE'])]
    public function delete(string $id, DeleteWeightUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new DeleteWeightDataInput($id)));
    }

    #[Route(path: '/api/player/tracking/weight', name: 'player_tracking_weight_list', methods: ['GET'])]
    public function list(Request $request, ListWeightForRangeUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new ListWeightForRangeDataInput(
            self::parseDate((string) $request->query->get('from', ''), 'from'),
            self::parseDate((string) $request->query->get('to', ''), 'to'),
        )));
    }

    private static function parseDate(string $raw, string $field): \DateTimeImmutable
    {
        if ('' === $raw) {
            throw new ValidationException('Weight request is invalid.', [$field => [sprintf('%s is required.', $field)]], self::BODY_INVALID);
        }

        try {
            return new \DateTimeImmutable($raw);
        } catch (\Exception) {
            throw new ValidationException('Weight request is invalid.', [$field => [sprintf('%s is not a valid datetime.', $field)]], self::BODY_INVALID);
        }
    }
}
