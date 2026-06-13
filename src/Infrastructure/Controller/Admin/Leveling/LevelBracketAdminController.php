<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin\Leveling;

use App\Domain\DTO\DataInput\Admin\Leveling\LevelBracket\CreateLevelBracketDataInput;
use App\Domain\DTO\DataInput\Admin\Leveling\LevelBracket\DeleteLevelBracketDataInput;
use App\Domain\DTO\DataInput\Admin\Leveling\LevelBracket\GetLevelBracketDetailsDataInput;
use App\Domain\DTO\DataInput\Admin\Leveling\LevelBracket\ListLevelBracketsDataInput;
use App\Domain\DTO\DataInput\Admin\Leveling\LevelBracket\UpdateLevelBracketDataInput;
use App\UseCase\Admin\Leveling\LevelBracket\CreateLevelBracketUseCase;
use App\UseCase\Admin\Leveling\LevelBracket\DeleteLevelBracketUseCase;
use App\UseCase\Admin\Leveling\LevelBracket\GetLevelBracketDetailsUseCase;
use App\UseCase\Admin\Leveling\LevelBracket\ListLevelBracketsUseCase;
use App\UseCase\Admin\Leveling\LevelBracket\UpdateLevelBracketUseCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class LevelBracketAdminController
{
    #[Route(path: '/api/admin/level-brackets', name: 'admin_level_bracket_list', methods: ['GET'])]
    public function list(ListLevelBracketsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new ListLevelBracketsDataInput()));
    }

    #[Route(path: '/api/admin/level-brackets/{id}', name: 'admin_level_bracket_get', methods: ['GET'])]
    public function get(string $id, GetLevelBracketDetailsUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new GetLevelBracketDetailsDataInput($id)));
    }

    #[Route(path: '/api/admin/level-brackets', name: 'admin_level_bracket_create', methods: ['POST'])]
    public function create(Request $request, CreateLevelBracketUseCase $useCase): JsonResponse
    {
        $payload = self::decodePayload($request);

        $output = $useCase->execute(new CreateLevelBracketDataInput(
            (int) ($payload['fromLevel'] ?? 0),
            self::nullableInt($payload['toLevel'] ?? null),
            (int) ($payload['coefficientA'] ?? 0),
            (int) ($payload['exponentK'] ?? 0),
            (int) ($payload['offsetB'] ?? 0),
        ));

        return new JsonResponse($output, 201);
    }

    #[Route(path: '/api/admin/level-brackets/{id}', name: 'admin_level_bracket_update', methods: ['PUT'])]
    public function update(string $id, Request $request, UpdateLevelBracketUseCase $useCase): JsonResponse
    {
        $payload = self::decodePayload($request);

        $output = $useCase->execute(new UpdateLevelBracketDataInput(
            $id,
            (int) ($payload['fromLevel'] ?? 0),
            self::nullableInt($payload['toLevel'] ?? null),
            (int) ($payload['coefficientA'] ?? 0),
            (int) ($payload['exponentK'] ?? 0),
            (int) ($payload['offsetB'] ?? 0),
        ));

        return new JsonResponse($output);
    }

    #[Route(path: '/api/admin/level-brackets/{id}', name: 'admin_level_bracket_delete', methods: ['DELETE'])]
    public function delete(string $id, DeleteLevelBracketUseCase $useCase): JsonResponse
    {
        $useCase->execute(new DeleteLevelBracketDataInput($id));

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

    private static function nullableInt(mixed $value): ?int
    {
        return null === $value || '' === $value ? null : (int) $value;
    }
}
