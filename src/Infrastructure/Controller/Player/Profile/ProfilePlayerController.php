<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Player\Profile;

use App\Domain\DTO\DataInput\Player\Profile\GetPlayerProfileDataInput;
use App\UseCase\Player\Profile\GetPlayerProfileUseCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ProfilePlayerController
{
    #[Route(path: '/api/player/profile', name: 'player_profile_get', methods: ['GET'])]
    public function get(GetPlayerProfileUseCase $useCase): JsonResponse
    {
        return new JsonResponse($useCase->execute(new GetPlayerProfileDataInput()));
    }
}
