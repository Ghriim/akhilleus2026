<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin\Questing;

use App\Domain\DTO\DataInput\Admin\Questing\Quest\CreateQuestDataInput;
use App\Domain\DTO\DataInput\Admin\Questing\Quest\DeleteQuestDataInput;
use App\Domain\DTO\DataInput\Admin\Questing\Quest\GetQuestDetailsDataInput;
use App\Domain\DTO\DataInput\Admin\Questing\Quest\ListQuestsDataInput;
use App\Domain\DTO\DataInput\Admin\Questing\Quest\UpdateQuestDataInput;
use App\Domain\Exception\ValidationException;
use App\UseCase\Admin\Questing\Quest\CreateQuestUseCase;
use App\UseCase\Admin\Questing\Quest\DeleteQuestUseCase;
use App\UseCase\Admin\Questing\Quest\GetQuestDetailsUseCase;
use App\UseCase\Admin\Questing\Quest\ListQuestsUseCase;
use App\UseCase\Admin\Questing\Quest\UpdateQuestUseCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class QuestAdminController
{
    private const string BODY_INVALID = 'QUEST_BODY_INVALID';

    #[Route(path: '/api/admin/quests', name: 'admin_quest_list', methods: ['GET'])]
    public function list(ListQuestsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new ListQuestsDataInput()));
    }

    #[Route(path: '/api/admin/quests/{id}', name: 'admin_quest_get', methods: ['GET'])]
    public function get(string $id, GetQuestDetailsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new GetQuestDetailsDataInput($id)));
    }

    #[Route(path: '/api/admin/quests', name: 'admin_quest_create', methods: ['POST'])]
    public function create(Request $request, CreateQuestUseCase $useCase): JsonResponse
    {
        $payload = self::decodePayload($request);

        $output = $useCase->execute(new CreateQuestDataInput(
            (string) ($payload['label'] ?? ''),
            (string) ($payload['kind'] ?? ''),
            (string) ($payload['periodicity'] ?? ''),
            (int) ($payload['rewardedXp'] ?? 0),
            self::nullableString($payload['metric'] ?? null),
            self::nullableString($payload['targetValue'] ?? null),
            self::nullableDate($payload['dateStart'] ?? null, 'dateStart'),
            self::nullableDate($payload['dateEnd'] ?? null, 'dateEnd'),
        ));

        return new JsonResponse($output, 201);
    }

    #[Route(path: '/api/admin/quests/{id}', name: 'admin_quest_update', methods: ['PUT'])]
    public function update(string $id, Request $request, UpdateQuestUseCase $useCase): JsonResponse
    {
        $payload = self::decodePayload($request);

        $output = $useCase->execute(new UpdateQuestDataInput(
            $id,
            (string) ($payload['label'] ?? ''),
            (string) ($payload['kind'] ?? ''),
            (string) ($payload['periodicity'] ?? ''),
            (int) ($payload['rewardedXp'] ?? 0),
            self::nullableString($payload['metric'] ?? null),
            self::nullableString($payload['targetValue'] ?? null),
            self::requiredDate($payload['dateStart'] ?? null, 'dateStart'),
            self::nullableDate($payload['dateEnd'] ?? null, 'dateEnd'),
        ));

        return new JsonResponse($output);
    }

    #[Route(path: '/api/admin/quests/{id}', name: 'admin_quest_delete', methods: ['DELETE'])]
    public function delete(string $id, DeleteQuestUseCase $useCase): JsonResponse
    {
        $useCase->execute(new DeleteQuestDataInput($id));

        return new JsonResponse(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodePayload(Request $request): array
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        return $payload;
    }

    private static function nullableString(mixed $value): ?string
    {
        return null === $value || '' === $value ? null : (string) $value;
    }

    private static function nullableDate(mixed $value, string $field): ?\DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return self::parseDate((string) $value, $field);
    }

    private static function requiredDate(mixed $value, string $field): \DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            throw new ValidationException('Quest request is invalid.', [$field => [sprintf('%s is required.', $field)]], self::BODY_INVALID);
        }

        return self::parseDate((string) $value, $field);
    }

    private static function parseDate(string $raw, string $field): \DateTimeImmutable
    {
        try {
            return new \DateTimeImmutable($raw);
        } catch (\Exception) {
            throw new ValidationException('Quest request is invalid.', [$field => [sprintf('%s is not a valid date.', $field)]], self::BODY_INVALID);
        }
    }
}
