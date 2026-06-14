<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Admin\Leveling;

use App\Domain\DTO\DataInput\Admin\Leveling\LevelingConfig\GetLevelingConfigDataInput;
use App\Domain\DTO\DataInput\Admin\Leveling\LevelingConfig\UpdateLevelingConfigDataInput;
use App\UseCase\Admin\Leveling\LevelingConfig\GetLevelingConfigUseCase;
use App\UseCase\Admin\Leveling\LevelingConfig\UpdateLevelingConfigUseCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class LevelingConfigAdminController
{
    #[Route(path: '/api/admin/leveling-config', name: 'admin_leveling_config_get', methods: ['GET'])]
    public function get(GetLevelingConfigUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new GetLevelingConfigDataInput()));
    }

    #[Route(path: '/api/admin/leveling-config', name: 'admin_leveling_config_update', methods: ['PUT'])]
    public function update(Request $request, UpdateLevelingConfigUseCase $useCase): JsonResponse
    {
        $payload = self::decodePayload($request);

        $output = $useCase->execute(new UpdateLevelingConfigDataInput(
            (int) ($payload['xpPerWorkoutMinute'] ?? 0),
        ));

        return new JsonResponse($output);
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
}
